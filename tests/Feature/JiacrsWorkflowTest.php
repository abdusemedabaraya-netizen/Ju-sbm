<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Evidence;
use App\Models\Investigation;
use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class JiacrsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private Category $category;

    private User $admin;

    private User $investigator;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Super Admin', 'Admin', 'Investigator', 'User'] as $role) {
            Role::findOrCreate($role);
        }

        $this->department = Department::create(['name' => 'General']);
        $this->category = Category::create([
            'slug' => 'bribery',
            'name' => 'Bribery',
            'default_priority' => 'high',
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');

        $this->investigator = User::factory()->create();
        $this->investigator->assignRole('Investigator');
    }

    private function makeReport(array $overrides = []): Report
    {
        return Report::create(array_merge([
            'tracking_number' => 'JU-2026-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'user_id' => null,
            'department_id' => $this->department->id,
            'subject' => 'Test report',
            'description' => 'Test description.',
            'category' => 'bribery',
            'priority' => 'high',
            'status' => 'submitted',
            'anonymous' => true,
        ], $overrides));
    }

    // -----------------------------------------------------------------
    // Happy path: steps 1 - 17
    // -----------------------------------------------------------------

    public function test_full_workflow_from_anonymous_submission_to_closure(): void
    {
        Storage::fake('local');

        // -------------------------------------------------------------
        // Step 1: anonymous user visits homepage
        // -------------------------------------------------------------
        $this->get('/')->assertOk()->assertSee('Submit Report')->assertSee('Track Report');

        // -------------------------------------------------------------
        // Step 2: open the submit-report form
        // -------------------------------------------------------------
        $this->get('/report')->assertOk()
            ->assertSee('Type of concern')
            ->assertSee('Department')
            ->assertSee('Evidence');

        // -------------------------------------------------------------
        // Step 3 + 4: submit an anonymous report WITH evidence
        // -------------------------------------------------------------
        $pdf = UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf');
        $jpg = UploadedFile::fake()->image('photo.jpg', 24, 24);

        $submit = $this->post('/report', [
            'department_id' => $this->department->id,
            'category' => 'bribery',
            'subject' => 'Bribe demanded for admission',
            'description' => 'An official asked for money to process an admission.',
            'incident_date' => now()->toDateString(),
            'location' => 'Main office',
            'anonymous' => true,
            'files' => [$pdf, $jpg],
        ])->assertRedirect(route('report.submitted'));

        $report = Report::firstOrFail();
        $this->assertSame('submitted', $report->status);
        $this->assertTrue($report->anonymous);
        $this->assertNull($report->user_id);
        $this->assertSame('high', $report->priority, 'Bribery category should auto-set high priority.');
        $this->assertMatchesRegularExpression('/^JU-\d{4}-\d{6}$/', $report->tracking_number);
        $this->assertNotNull($report->access_code_hash);
        $this->assertSame(2, $report->evidences()->count(), 'Both evidence files should be stored.');

        // The access code is shown once on the confirmation page.
        $accessCode = session('access_code');
        $this->assertNotNull($accessCode);
        $this->get(route('report.submitted'))->assertOk()->assertSee($report->tracking_number);

        // Database: first status history + audit entry recorded at submission.
        $this->assertDatabaseHas('report_status_histories', [
            'report_id' => $report->id,
            'to_status' => 'submitted',
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.submitted']);

        // Admins were notified about the new report (database notification).
        $adminNotifications = $this->admin->notifications()->count();
        $this->assertSame(1, $adminNotifications, 'Admin should receive one new-report notification.');

        // -------------------------------------------------------------
        // Step 5: track the report with tracking number + access code
        // -------------------------------------------------------------
        $this->post('/track', [
            'tracking_number' => $report->tracking_number,
            'access_code' => $accessCode,
        ])->assertOk()
            ->assertSee($report->tracking_number)
            ->assertSee('Submitted');

        // Wrong access code must NOT reveal the report.
        $this->from('/track')->post('/track', [
            'tracking_number' => $report->tracking_number,
            'access_code' => 'WRONGCODE1',
        ])->assertRedirect('/track')->assertSessionHasErrors('tracking_number');

        // -------------------------------------------------------------
        // Step 6: admin logs in (real login -> audit log)
        // -------------------------------------------------------------
        $this->post('/login', [
            'email' => $this->admin->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->admin);
        $this->assertDatabaseHas('audit_logs', ['action' => 'auth.login', 'user_id' => $this->admin->id]);

        $this->get(route('admin.dashboard'))->assertOk();

        // -------------------------------------------------------------
        // Step 7: admin reviews the report
        // -------------------------------------------------------------
        $this->get(route('reports.show', $report))->assertOk()
            ->assertSee($report->tracking_number)
            ->assertSee('Anonymous');

        // -------------------------------------------------------------
        // Step 8: admin moves the report to Under Review
        // -------------------------------------------------------------
        $this->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'under_review'])
            ->assertSessionHasNoErrors();
        $this->assertSame('under_review', $report->fresh()->status);

        // -------------------------------------------------------------
        // Step 9: admin assigns an investigator
        // -------------------------------------------------------------
        $this->post(route('investigations.store'), [
            'report_id' => $report->id,
            'investigator_id' => $this->investigator->id,
        ])->assertRedirect(route('reports.show', $report));

        $investigation = Investigation::where('report_id', $report->id)->firstOrFail();
        $this->assertSame($this->investigator->id, $investigation->investigator_id);
        $this->assertSame('assigned', $report->fresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'investigation.assigned']);
        $this->assertSame(1, $this->investigator->notifications()->count(), 'Investigator should be notified of the assignment.');

        // -------------------------------------------------------------
        // Step 10: investigator logs in
        // -------------------------------------------------------------
        Auth::logout();
        $this->post('/login', ['email' => $this->investigator->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->investigator);

        // -------------------------------------------------------------
        // Step 11: investigator sees only the assigned case
        // -------------------------------------------------------------
        $otherInvestigator = User::factory()->create();
        $otherInvestigator->assignRole('Investigator');
        $otherReport = $this->makeReport(['subject' => 'Someone else case']);
        $otherReport->update(['status' => 'assigned']);
        $otherInvestigation = Investigation::create([
            'report_id' => $otherReport->id,
            'investigator_id' => $otherInvestigator->id,
        ]);

        $this->get(route('reports.show', $report))->assertOk()->assertSee($report->tracking_number);
        $this->get(route('reports.show', $otherReport))->assertForbidden();
        $this->get(route('investigations.show', $investigation))->assertOk();
        $this->get(route('investigations.show', $otherInvestigation))->assertForbidden();

        // -------------------------------------------------------------
        // Step 12: investigator reviews evidence (downloads it)
        // -------------------------------------------------------------
        $evidence = $report->evidences()->firstOrFail();
        $this->get(route('evidence.download', $evidence))->assertOk();
        // An unrelated investigator cannot download it.
        Auth::logout();
        $this->actingAs($otherInvestigator)->get(route('evidence.download', $evidence))->assertForbidden();
        Auth::logout();
        $this->actingAs($this->investigator);

        // -------------------------------------------------------------
        // Step 13: investigator adds findings
        // -------------------------------------------------------------
        $this->put(route('investigations.update', $investigation), [
            'findings' => 'Money was solicited on 2026-10-01.',
            'recommendations' => 'Refer to the disciplinary committee.',
        ])->assertSessionHasNoErrors();

        $investigation->refresh();
        $this->assertSame('Money was solicited on 2026-10-01.', $investigation->findings);
        $this->assertSame('Refer to the disciplinary committee.', $investigation->recommendation);
        $this->assertSame('Refer to the disciplinary committee.', $investigation->recommendations);
        $this->assertDatabaseHas('audit_logs', ['action' => 'investigation.updated']);

        // -------------------------------------------------------------
        // Step 14: investigator moves the case to Investigating
        // -------------------------------------------------------------
        $this->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'investigating'])
            ->assertSessionHasNoErrors();
        $this->assertSame('investigating', $report->fresh()->status);

        // -------------------------------------------------------------
        // Step 15: investigator resolves the case
        // -------------------------------------------------------------
        $this->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'resolved'])
            ->assertSessionHasNoErrors();
        $this->assertSame('resolved', $report->fresh()->status);

        // -------------------------------------------------------------
        // Step 16: admin reviews findings
        // -------------------------------------------------------------
        Auth::logout();
        $this->actingAs($this->admin);
        $this->get(route('reports.show', $report))->assertOk()
            ->assertSee('Money was solicited')
            ->assertSee('disciplinary committee');

        // -------------------------------------------------------------
        // Step 17: admin closes the case
        // -------------------------------------------------------------
        $this->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'closed'])
            ->assertSessionHasNoErrors();
        $this->assertSame('closed', $report->fresh()->status);

        // -------------------------------------------------------------
        // Final integrity checks: status history + audit trail
        // -------------------------------------------------------------
        $path = ReportStatusHistory::where('report_id', $report->id)
            ->orderBy('id')->pluck('to_status')->all();
        $this->assertSame(
            ['submitted', 'under_review', 'assigned', 'investigating', 'resolved', 'closed'],
            $path,
            'The full status history must be recorded in order.'
        );

        $this->assertSame(0, ReportStatusHistory::where('report_id', $report->id)
            ->whereNull('changed_by')->where('to_status', '!=', 'submitted')->count(),
            'Every post-submission change must record the acting user.');

        // Audit entries are linked to the report they concern.
        $morph = $report->getMorphClass();
        $this->assertSame(1, AuditLog::where('action', 'report.submitted')
            ->where('subject_type', $morph)->where('subject_id', $report->id)->count());
        $this->assertSame(4, AuditLog::where('action', 'report.status_changed')
            ->where('subject_type', $morph)->where('subject_id', $report->id)->count(),
            'under_review, investigating, resolved and closed are reported as status changes.');
    }

    // -----------------------------------------------------------------
    // Invalid status transitions
    // -----------------------------------------------------------------

    public function test_submitted_cannot_be_closed_directly(): void
    {
        $report = $this->makeReport(['status' => 'submitted']);

        $this->actingAs($this->admin)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'closed'])
            ->assertSessionHasErrors('status');

        $this->assertSame('submitted', $report->fresh()->status);
    }

    public function test_rejected_cannot_be_investigated(): void
    {
        $report = $this->makeReport(['status' => 'rejected']);

        $this->actingAs($this->admin)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'investigating'])
            ->assertSessionHasErrors('status');

        $this->assertSame('rejected', $report->fresh()->status);
    }

    public function test_closed_cannot_be_reopened(): void
    {
        $report = $this->makeReport(['status' => 'closed']);

        $this->actingAs($this->admin)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'investigating'])
            ->assertSessionHasErrors('status');

        $this->assertSame('closed', $report->fresh()->status);
    }

    public function test_investigator_cannot_skip_investigating_step(): void
    {
        $report = $this->makeReport(['status' => 'assigned']);
        Investigation::create([
            'report_id' => $report->id,
            'investigator_id' => $this->investigator->id,
        ]);

        $this->actingAs($this->investigator)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'resolved'])
            ->assertSessionHasErrors('status');

        $this->assertSame('assigned', $report->fresh()->status);
    }

    // -----------------------------------------------------------------
    // Permission boundaries
    // -----------------------------------------------------------------

    public function test_reporter_cannot_access_admin_pages(): void
    {
        $reporter = User::factory()->create();
        $reporter->assignRole('User');

        $this->actingAs($reporter);

        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->get(route('reports.index'))->assertForbidden();
        $this->get(route('investigations.index'))->assertForbidden();
        $this->get(route('audit-logs.index'))->assertForbidden();
    }

    public function test_investigator_cannot_delete_reports(): void
    {
        $report = $this->makeReport();
        Investigation::create([
            'report_id' => $report->id,
            'investigator_id' => $this->investigator->id,
        ]);

        $this->actingAs($this->investigator)
            ->delete(route('reports.destroy', $report))
            ->assertForbidden();

        $this->assertDatabaseHas('reports', ['id' => $report->id, 'deleted_at' => null]);
    }

    public function test_investigator_cannot_delete_investigations(): void
    {
        $report = $this->makeReport(['status' => 'assigned']);
        $investigation = Investigation::create([
            'report_id' => $report->id,
            'investigator_id' => $this->investigator->id,
        ]);

        $this->actingAs($this->investigator)
            ->delete(route('investigations.destroy', $investigation))
            ->assertForbidden();

        $this->assertDatabaseHas('investigations', ['id' => $investigation->id]);
    }

    public function test_anonymous_visitor_cannot_access_protected_pages(): void
    {
        $report = $this->makeReport();

        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('reports.index'))->assertRedirect(route('login'));
        $this->get(route('reports.show', $report))->assertRedirect(route('login'));
        $this->get(route('my.reports.index'))->assertRedirect(route('login'));
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
    }

    // -----------------------------------------------------------------
    // Notifications for a registered reporter
    // -----------------------------------------------------------------

    public function test_registered_reporter_receives_status_notifications(): void
    {
        Storage::fake('local');

        $reporter = User::factory()->create();
        $reporter->assignRole('User');

        $this->actingAs($reporter)->post('/report', [
            'department_id' => $this->department->id,
            'category' => 'bribery',
            'subject' => 'Registered report',
            'description' => 'A registered user submits a report.',
            'anonymous' => false,
        ])->assertRedirect(route('report.submitted'));

        $report = Report::firstOrFail();
        $this->assertSame($reporter->id, $report->user_id);
        $this->assertFalse($report->anonymous);

        // Reporter can see it under "my reports".
        $this->get(route('my.reports.index'))->assertOk()->assertSee($report->tracking_number);

        // A status change notifies the reporter. They already have the
        // submission confirmation, so there are now two notifications.
        Auth::logout();
        $this->actingAs($this->admin)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'under_review'])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, $reporter->fresh()->notifications()->count());
    }

    // -----------------------------------------------------------------
    // "Type of concern" select must offer the managed categories
    // -----------------------------------------------------------------

    public function test_type_of_concern_select_lists_all_managed_categories(): void
    {
        $this->seed(CategorySeeder::class);

        $response = $this->get('/report');
        $response->assertOk();

        foreach ([
            'bribery', 'fraud', 'abuse_of_power', 'nepotism',
            'resource_misuse', 'harassment', 'other',
        ] as $slug) {
            $response->assertSee('value="'.$slug.'"', false);
        }

        $response->assertSee('name="category"', false);
        $response->assertSee('required', false);
    }

    public function test_submission_rejects_an_unknown_category(): void
    {
        $this->post('/report', [
            'department_id' => $this->department->id,
            'category' => 'not_a_real_category',
            'subject' => 'Bogus',
            'description' => 'Bogus category must be rejected.',
        ])->assertSessionHasErrors('category');

        $this->assertSame(0, Report::count());
    }
}
