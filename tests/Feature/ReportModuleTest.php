<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Evidence;
use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * QA suite for the JIACRS Report Module, from the reporter's point of view.
 *
 * Steps 1-9 (homepage, form fields, anonymous submission, evidence,
 * tracking) plus the invalid submissions an attacker/reporter could try.
 */
class ReportModuleTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Super Admin', 'Admin', 'Investigator', 'User'] as $role) {
            Role::findOrCreate($role);
        }

        Storage::fake('local');

        $this->department = Department::create(['name' => 'General']);
        Category::create([
            'slug' => 'bribery', 'name' => 'Bribery', 'default_priority' => 'high',
            'is_active' => true, 'sort_order' => 0,
        ]);
        Category::create([
            'slug' => 'fraud', 'name' => 'Fraud', 'default_priority' => 'critical',
            'is_active' => true, 'sort_order' => 1,
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'department_id' => $this->department->id,
            'category' => 'bribery',
            'subject' => 'Bribe demanded for admission',
            'description' => 'An official asked for money to process an admission.',
            'incident_date' => now()->subDay()->toDateString(),
            'location' => 'Main office',
            'anonymous' => true,
        ], $overrides);
    }

    // =================================================================
    // Steps 1-3: homepage + submit form + all required fields present
    // =================================================================

    public function test_step_1_homepage_loads_with_report_entry_points(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('Submit Anonymous Report')
            ->assertSee(route('report.create'))
            ->assertSee('Track Report');
    }

    public function test_steps_2_to_3_submit_form_exposes_every_required_field(): void
    {
        $response = $this->get('/report');
        $response->assertOk();

        // Every field the reporter must be able to reach.
        $response->assertSee('name="subject"', false);
        $response->assertSee('name="category"', false);
        $response->assertSee('name="department_id"', false);
        $response->assertSee('name="description"', false);
        $response->assertSee('name="incident_date"', false);
        $response->assertSee('name="location"', false);
        $response->assertSee('name="files[]"', false);
        $response->assertSee('enctype="multipart/form-data"', false);

        // Required markers on the mandatory controls.
        $response->assertSee('id="subject"', false);
        $response->assertSee('id="description"', false);
        $response->assertSee('accept=".pdf,.docx,.jpg,.jpeg,.png,.mp4"', false);

        // Guests are anonymous by definition, so they get a notice instead of a checkbox.
        $response->assertSee('reporting anonymously', false);
        $response->assertDontSee('name="anonymous"', false);
    }

    public function test_authenticated_reporter_sees_the_anonymous_option_checked_by_default(): void
    {
        $reporter = User::factory()->create();
        $reporter->assignRole('User');

        $this->actingAs($reporter)->get('/report')->assertOk()
            ->assertSee('name="anonymous"', false)
            ->assertSee('Submit anonymously', false);
    }

    // =================================================================
    // Steps 4-5: valid anonymous submission -> saved, tracked, audited
    // =================================================================

    public function test_steps_4_to_5_anonymous_submission_is_saved_tracked_and_audited(): void
    {
        $this->post('/report', $this->validPayload())
            ->assertRedirect(route('report.submitted'));

        $report = Report::firstOrFail();

        // 5a. Report saved with the reporter's data.
        $this->assertSame('submitted', $report->status);
        $this->assertTrue($report->anonymous);
        $this->assertNull($report->user_id);
        $this->assertSame('Bribe demanded for admission', $report->subject);
        $this->assertSame('high', $report->priority, 'Bribery must auto-set high priority.');
        $this->assertNotNull($report->access_code_hash);
        $this->assertSame($report->access_code_hash, substr($report->access_code_hash, 0));

        // 5b. Tracking number generated in JU-YYYY-NNNNNN form.
        $this->assertMatchesRegularExpression('/^JU-\d{4}-\d{6}$/', $report->tracking_number);

        // 5c. Audit log + first status history row.
        $this->assertDatabaseHas('audit_logs', ['action' => 'report.submitted']);
        $this->assertDatabaseHas('report_status_histories', [
            'report_id' => $report->id,
            'to_status' => 'submitted',
        ]);

        // The access code is flashed exactly once; capture it before the GET consumes it.
        $accessCode = session('access_code');
        $this->assertNotNull($accessCode, 'The access code must be flashed after submission.');

        // The confirmation page reveals the tracking number and the access code once.
        // The view groups the code for readability (e.g. XXXXX-XXXXX).
        $this->get(route('report.submitted'))->assertOk()
            ->assertSee($report->tracking_number)
            ->assertSee(substr($accessCode, 0, 5))
            ->assertSee(substr($accessCode, 5));
    }

    public function test_logged_in_reporter_can_choose_not_to_be_anonymous(): void
    {
        $reporter = User::factory()->create();
        $reporter->assignRole('User');

        $this->actingAs($reporter)->post('/report', $this->validPayload([
            'anonymous' => false,
        ]))->assertRedirect(route('my.reports.submitted'));

        $report = Report::firstOrFail();
        $this->assertFalse($report->anonymous);
        $this->assertSame($reporter->id, $report->user_id);
    }

    public function test_authenticated_reporter_default_form_submission_is_anonymous(): void
    {
        $reporter = User::factory()->create();
        $reporter->assignRole('User');

        // The checkbox is checked by default, so the browser submits anonymous=1.
        $this->actingAs($reporter)->post('/report', $this->validPayload([
            'anonymous' => '1',
        ]))->assertRedirect(route('my.reports.submitted'));

        $report = Report::firstOrFail();
        $this->assertTrue($report->anonymous);
        $this->assertNull($report->user_id);
    }

    public function test_authenticated_reporter_unchecking_anonymous_links_the_report_to_the_account(): void
    {
        $reporter = User::factory()->create();
        $reporter->assignRole('User');

        // Browsers omit an unchecked checkbox entirely.
        $payload = $this->validPayload();
        unset($payload['anonymous']);

        $this->actingAs($reporter)->post('/report', $payload)
            ->assertRedirect(route('my.reports.submitted'));

        $report = Report::firstOrFail();
        $this->assertFalse($report->anonymous);
        $this->assertSame($reporter->id, $report->user_id);
    }

    // =================================================================
    // Steps 6-7: evidence upload, validation and private storage
    // =================================================================

    public function test_steps_6_to_7_valid_evidence_is_validated_and_stored_privately(): void
    {
        $pdf = UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf');
        $jpg = UploadedFile::fake()->image('photo.jpg', 30, 30);

        $this->post('/report', $this->validPayload(['files' => [$pdf, $jpg]]))
            ->assertRedirect(route('report.submitted'));

        $report = Report::firstOrFail();
        $this->assertSame(2, $report->evidences()->count());

        foreach ($report->evidences as $evidence) {
            // Stored on the PRIVATE disk, never the public one.
            Storage::disk('local')->assertExists($evidence->stored_path);
            Storage::disk('public')->assertMissing($evidence->stored_path);

            $this->assertNotNull($evidence->sha256);
            $this->assertGreaterThan(0, $evidence->size);
        }

        // Anonymous uploads get neutral file names (no identifying metadata).
        $jpgEvidence = $report->evidences()->where('mime_type', 'image/jpeg')->firstOrFail();
        $this->assertTrue($jpgEvidence->metadata_stripped, 'Images must be re-encoded to drop EXIF (GPS/camera).');
        $this->assertSame('evidence-2.jpg', $jpgEvidence->original_name);
    }

    public function test_evidence_rejects_unsupported_file_type(): void
    {
        $evil = UploadedFile::fake()->create('payload.exe', 10, 'application/x-msdownload');

        $this->post('/report', $this->validPayload(['files' => [$evil]]))
            ->assertSessionHasErrors('files.0');

        $this->assertSame(0, Report::count(), 'No report must be created when evidence is rejected.');
        $this->assertSame(0, Evidence::count());
    }

    public function test_evidence_rejects_oversized_file(): void
    {
        // 21 MB > the 20 MB (20480 KB) SRS limit.
        $big = UploadedFile::fake()->create('huge.pdf', 21000, 'application/pdf');

        $this->post('/report', $this->validPayload(['files' => [$big]]))
            ->assertSessionHasErrors('files.0');

        $this->assertSame(0, Report::count());
    }

    public function test_evidence_rejects_too_many_files(): void
    {
        $files = [];
        for ($i = 0; $i < 6; $i++) {            // limit is 5
            $files[] = UploadedFile::fake()->create("f{$i}.pdf", 5, 'application/pdf');
        }

        $this->post('/report', $this->validPayload(['files' => $files]))
            ->assertSessionHasErrors('files');

        $this->assertSame(0, Report::count());
    }

    // =================================================================
    // Steps 8-9: tracking with the number + access code
    // =================================================================

    public function test_steps_8_to_9_tracking_shows_the_correct_report(): void
    {
        $this->post('/report', $this->validPayload());
        $report = Report::firstOrFail();
        $accessCode = session('access_code');

        $this->from('/track')->post('/track', [
            'tracking_number' => $report->tracking_number,
            'access_code' => $accessCode,
        ])->assertOk()
            ->assertSee($report->tracking_number)
            ->assertSee('Bribery')
            ->assertSee('Submitted');

        // The access code is accepted with the readable separator too.
        $grouped = substr($accessCode, 0, 5).'-'.substr($accessCode, 5);
        $this->post('/track', [
            'tracking_number' => $report->tracking_number,
            'access_code' => strtolower($grouped),
        ])->assertOk()->assertSee($report->tracking_number);
    }

    public function test_expected_flow_tracking_shows_under_review_after_admin_accepts(): void
    {
        $this->post('/report', $this->validPayload());
        $report = Report::firstOrFail();
        $accessCode = session('access_code');

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin)->patch(route('reports.update', $report), ['status' => 'under_review'])
            ->assertSessionHasNoErrors();

        $this->post('/track', [
            'tracking_number' => $report->tracking_number,
            'access_code' => $accessCode,
        ])->assertOk()
            ->assertSee($report->tracking_number)
            ->assertSee('Under Review');
    }

    // =================================================================
    // Step 10: invalid submissions and abuse attempts
    // =================================================================

    public function test_invalid_empty_submission_reports_every_missing_field(): void
    {
        $this->post('/report', [])
            ->assertSessionHasErrors(['department_id', 'category', 'subject', 'description']);

        $this->assertSame(0, Report::count());
    }

    public function test_invalid_unknown_category_is_rejected(): void
    {
        $this->post('/report', $this->validPayload(['category' => 'not_a_category']))
            ->assertSessionHasErrors('category');

        $this->assertSame(0, Report::count());
    }

    public function test_invalid_future_incident_date_is_rejected(): void
    {
        $this->post('/report', $this->validPayload(['incident_date' => now()->addWeek()->toDateString()]))
            ->assertSessionHasErrors('incident_date');

        $this->assertSame(0, Report::count());
    }

    public function test_invalid_unknown_department_is_rejected(): void
    {
        $this->post('/report', $this->validPayload(['department_id' => 99999]))
            ->assertSessionHasErrors('department_id');

        $this->assertSame(0, Report::count());
    }

    public function test_tracking_wrong_access_code_does_not_reveal_the_report(): void
    {
        $this->post('/report', $this->validPayload());
        $report = Report::firstOrFail();

        $this->from('/track')->post('/track', [
            'tracking_number' => $report->tracking_number,
            'access_code' => 'WRONGCODE1',
        ])->assertRedirect('/track')
            ->assertSessionHasErrors('tracking_number');

        // A wrong code must not reveal report details. The number the user typed is
        // echoed back as old input, but no status/category/result is ever shown.
        $this->get('/track')->assertOk()
            ->assertDontSee('Current status')
            ->assertDontSee('Progress')
            ->assertDontSee('Bribery');
    }

    public function test_duplicate_tracking_attempts_give_one_generic_message(): void
    {
        $this->post('/report', $this->validPayload());
        $report = Report::firstOrFail();

        // Attempt A: a real tracking number with the wrong code.
        $a = $this->from('/track')->post('/track', [
            'tracking_number' => $report->tracking_number,
            'access_code' => 'WRONGCODE1',
        ]);
        // Attempt B: a made-up tracking number with a made-up code.
        $b = $this->from('/track')->post('/track', [
            'tracking_number' => 'JU-2026-999999',
            'access_code' => 'WRONGCODE1',
        ]);

        $a->assertSessionHasErrors(['tracking_number' => 'No report matches that tracking number and access code.']);
        $b->assertSessionHasErrors(['tracking_number' => 'No report matches that tracking number and access code.']);
        $this->assertSame($a->getSession()->get('errors')->first('tracking_number'), $b->getSession()->get('errors')->first('tracking_number'));
    }

    public function test_duplicate_submissions_produce_distinct_tracking_numbers(): void
    {
        $this->post('/report', $this->validPayload());
        $first = Report::firstOrFail()->tracking_number;

        $this->post('/report', $this->validPayload());
        $second = Report::orderByDesc('id')->firstOrFail()->tracking_number;

        $this->assertNotSame($first, $second);
        $this->assertSame(2, Report::count());
    }

    public function test_tracking_requires_both_fields(): void
    {
        $this->post('/track', [])->assertSessionHasErrors(['tracking_number', 'access_code']);
        $this->post('/track', ['tracking_number' => 'JU-2026-000001'])
            ->assertSessionHasErrors('access_code');
    }

    public function test_confirmation_page_cannot_be_reopened_without_a_session(): void
    {
        $this->get(route('report.submitted'))->assertNotFound();
    }

    public function test_submitted_report_records_priority_from_category(): void
    {
        $this->post('/report', $this->validPayload(['category' => 'fraud']));
        $this->assertSame('critical', Report::firstOrFail()->priority);
    }

    public function test_report_status_history_is_ordered_newest_first_for_tracking(): void
    {
        $this->post('/report', $this->validPayload());
        $report = Report::firstOrFail();

        $admin = User::factory()->create();
        $admin->assignRole('Admin');
        $this->actingAs($admin)->patch(route('reports.update', $report), ['status' => 'under_review']);

        $this->assertSame(
            ['submitted', 'under_review'],
            ReportStatusHistory::where('report_id', $report->id)->orderBy('id')->pluck('to_status')->all()
        );
    }
}
