<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Department;
use App\Models\Investigation;
use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * QA suite for the JIACRS Investigation Module.
 *
 * Covers the assignment -> investigation -> closure flow (steps 1-15) and the
 * invalid workflows (crafted status jumps, forbidden actors). Every test logs
 * in through the real login endpoint so route middleware, policies and the
 * workflow state machine are all exercised.
 */
class InvestigationModuleTest extends TestCase
{
    use RefreshDatabase;

    private Department $department;

    private User $superAdmin;

    private User $admin;

    private User $investigator;

    private User $otherInvestigator;

    private User $reporter;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['Super Admin', 'Admin', 'Investigator', 'User'] as $role) {
            Role::findOrCreate($role);
        }

        $this->department = Department::create(['name' => 'General']);
        Category::create([
            'slug' => 'bribery', 'name' => 'Bribery', 'default_priority' => 'high',
            'is_active' => true, 'sort_order' => 0,
        ]);

        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole('Super Admin');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');

        $this->investigator = User::factory()->create();
        $this->investigator->assignRole('Investigator');

        $this->otherInvestigator = User::factory()->create();
        $this->otherInvestigator->assignRole('Investigator');

        $this->reporter = User::factory()->create();
        $this->reporter->assignRole('User');
    }

    private function makeReport(array $overrides = []): Report
    {
        return Report::create(array_merge([
            'tracking_number' => 'JU-2026-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT),
            'user_id' => null,
            'department_id' => $this->department->id,
            'subject' => 'QA investigation report',
            'description' => 'QA description.',
            'category' => 'bribery',
            'priority' => 'high',
            'status' => 'submitted',
            'anonymous' => true,
        ], $overrides));
    }

    /** Real login (exercises the auth pipeline, not actingAs). */
    private function loginAs(User $user): void
    {
        Auth::logout();
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    private function assign(Report $report, User $investigator, ?User $actor = null): void
    {
        $this->actingAs($actor ?? $this->admin)
            ->from(route('reports.show', $report))
            ->post(route('investigations.store'), [
                'report_id' => $report->id,
                'investigator_id' => $investigator->id,
            ]);
    }

    // =================================================================
    // Steps 1-4: admin assigns an investigator and every artifact is saved
    // =================================================================

    public function test_steps_1_to_4_assignment_saves_row_status_notification_and_audit(): void
    {
        $report = $this->makeReport(['status' => 'under_review']);

        $this->loginAs($this->admin);
        $this->get(route('reports.show', $report))->assertOk();

        $this->actingAs($this->admin)
            ->from(route('reports.show', $report))
            ->post(route('investigations.store'), [
                'report_id' => $report->id,
                'investigator_id' => $this->investigator->id,
            ])
            ->assertRedirect(route('reports.show', $report));

        // 4a. Assignment saved
        $investigation = Investigation::where('report_id', $report->id)->firstOrFail();
        $this->assertSame($this->investigator->id, $investigation->investigator_id);

        // 4b. Status changes to Assigned
        $this->assertSame('assigned', $report->fresh()->status);

        // 4c. Notification created for the investigator
        $this->assertSame(1, $this->investigator->fresh()->notifications()->count());
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $this->investigator->id,
            'notifiable_type' => $this->investigator->getMorphClass(),
        ]);

        // 4d. Audit log recorded
        $this->assertDatabaseHas('audit_logs', ['action' => 'investigation.assigned']);

        // Status history records who moved it and the note.
        $this->assertDatabaseHas('report_status_histories', [
            'report_id' => $report->id,
            'from_status' => 'under_review',
            'to_status' => 'assigned',
            'changed_by' => $this->admin->id,
        ]);
    }

    public function test_super_admin_can_also_assign(): void
    {
        $report = $this->makeReport(['status' => 'under_review']);

        $this->actingAs($this->superAdmin)
            ->from(route('reports.show', $report))
            ->post(route('investigations.store'), [
                'report_id' => $report->id,
                'investigator_id' => $this->investigator->id,
            ])
            ->assertRedirect(route('reports.show', $report));

        $this->assertSame('assigned', $report->fresh()->status);
    }

    public function test_assignment_is_refused_when_report_is_not_under_review(): void
    {
        $report = $this->makeReport(['status' => 'submitted']);

        $this->actingAs($this->admin)
            ->from(route('reports.show', $report))
            ->post(route('investigations.store'), [
                'report_id' => $report->id,
                'investigator_id' => $this->investigator->id,
            ]);

        $this->assertSame('submitted', $report->fresh()->status);
        $this->assertSame(0, Investigation::where('report_id', $report->id)->count());
    }

    public function test_conflict_of_interest_blocks_assigning_the_reporter(): void
    {
        $report = $this->makeReport([
            'status' => 'under_review',
            'user_id' => $this->investigator->id,
            'anonymous' => false,
        ]);

        $this->actingAs($this->admin)
            ->from(route('reports.show', $report))
            ->post(route('investigations.store'), [
                'report_id' => $report->id,
                'investigator_id' => $this->investigator->id,
            ])
            ->assertSessionHasErrors('investigator_id');

        $this->assertSame('under_review', $report->fresh()->status);
        $this->assertSame(0, Investigation::where('report_id', $report->id)->count());
    }

    // =================================================================
    // Steps 5-6: investigator logs in and sees ONLY assigned cases
    // =================================================================

    public function test_steps_5_to_6_investigator_sees_only_assigned_cases(): void
    {
        $mine = $this->makeReport(['status' => 'under_review']);
        $this->assign($mine, $this->investigator);

        $theirs = $this->makeReport(['status' => 'under_review']);
        $this->assign($theirs, $this->otherInvestigator);

        $this->loginAs($this->investigator);

        $myInvestigation = Investigation::where('report_id', $mine->id)->firstOrFail();
        $theirInvestigation = Investigation::where('report_id', $theirs->id)->firstOrFail();

        // Visible: the assigned case.
        $this->get(route('investigations.index'))->assertOk()->assertSee($mine->tracking_number);
        $this->get(route('reports.index'))->assertOk()->assertSee($mine->tracking_number);
        $this->get(route('investigations.show', $myInvestigation))->assertOk();
        $this->get(route('reports.show', $mine))->assertOk();

        // Not visible: someone else's case.
        $this->get(route('investigations.index'))->assertDontSee($theirs->tracking_number);
        $this->get(route('reports.index'))->assertDontSee($theirs->tracking_number);
        $this->get(route('investigations.show', $theirInvestigation))->assertForbidden();
        $this->get(route('reports.show', $theirs))->assertForbidden();
    }

    // =================================================================
    // Steps 7-8: open the assigned case, review details and evidence
    // =================================================================

    public function test_steps_7_to_8_case_workspace_and_evidence_are_accessible_to_owner_only(): void
    {
        Storage::fake('local');

        $report = $this->makeReport(['status' => 'under_review']);
        $pdf = \Illuminate\Http\UploadedFile::fake()->create('proof.pdf', 20, 'application/pdf');
        $this->actingAs($this->admin)
            ->post(route('evidence.store', $report), ['files' => [$pdf]])
            ->assertSessionHasNoErrors();

        $this->assign($report, $this->investigator);
        $investigation = Investigation::where('report_id', $report->id)->firstOrFail();
        $evidence = $report->evidences()->firstOrFail();

        $this->loginAs($this->investigator);
        $this->get(route('investigations.show', $investigation))->assertOk()
            ->assertSee($report->tracking_number);
        $this->get(route('evidence.download', $evidence))->assertOk();

        // Another investigator (not on the case) cannot open it or its evidence.
        $this->loginAs($this->otherInvestigator);
        $this->get(route('investigations.show', $investigation))->assertForbidden();
        $this->get(route('evidence.download', $evidence))->assertForbidden();
    }

    // =================================================================
    // Steps 9-12: findings, Assigned -> Investigating -> Resolved
    // =================================================================

    public function test_steps_9_to_12_findings_and_investigator_status_progression(): void
    {
        $report = $this->makeReport(['status' => 'submitted']);

        // Admin accepts the report first so the full history path is recorded.
        $this->actingAs($this->admin)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'under_review'])
            ->assertSessionHasNoErrors();

        $this->assign($report, $this->investigator);
        $investigation = Investigation::where('report_id', $report->id)->firstOrFail();

        $this->loginAs($this->investigator);

        // Step 9: record findings + recommendations.
        $this->put(route('investigations.update', $investigation), [
            'findings' => 'QA findings.',
            'recommendations' => 'QA recommendation.',
        ])->assertSessionHasNoErrors();

        $investigation->refresh();
        $this->assertSame('QA findings.', $investigation->findings);
        $this->assertSame('QA recommendation.', $investigation->recommendations);
        $this->assertDatabaseHas('audit_logs', ['action' => 'investigation.updated']);

        // Step 10: Assigned -> Investigating via the case workspace button.
        $this->from(route('investigations.show', $investigation))
            ->post(route('investigations.start', $investigation))
            ->assertSessionHasNoErrors();
        $this->assertSame('investigating', $report->fresh()->status);

        // Step 11 + 12: Investigating -> Resolved.
        $this->from(route('investigations.show', $investigation))
            ->post(route('investigations.resolve', $investigation))
            ->assertSessionHasNoErrors();
        $this->assertSame('resolved', $report->fresh()->status);

        $this->assertSame(
            ['under_review', 'assigned', 'investigating', 'resolved'],
            ReportStatusHistory::where('report_id', $report->id)->orderBy('id')->pluck('to_status')->all()
        );
    }

    public function test_resolve_is_blocked_without_findings(): void
    {
        $report = $this->makeReport(['status' => 'investigating']);
        $investigation = Investigation::create([
            'report_id' => $report->id,
            'investigator_id' => $this->investigator->id,
        ]);

        $this->actingAs($this->investigator)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'resolved'])
            ->assertSessionHasErrors('status');

        $this->assertSame('investigating', $report->fresh()->status);
    }

    public function test_start_is_blocked_unless_status_is_assigned(): void
    {
        $report = $this->makeReport(['status' => 'investigating']);
        $investigation = Investigation::create([
            'report_id' => $report->id,
            'investigator_id' => $this->investigator->id,
        ]);

        $this->actingAs($this->investigator)
            ->from(route('investigations.show', $investigation))
            ->post(route('investigations.start', $investigation))
            ->assertForbidden();
    }

    // =================================================================
    // Steps 13-15: admin reviews the findings and closes the case
    // =================================================================

    public function test_steps_13_to_15_admin_reviews_and_closes_resolved_case(): void
    {
        $report = $this->makeReport(['status' => 'investigating']);
        $investigation = Investigation::create([
            'report_id' => $report->id,
            'investigator_id' => $this->investigator->id,
            'findings' => 'QA findings to review.',
            'recommendations' => 'QA recommendation.',
        ]);
        $report->update(['status' => 'resolved']);

        $this->loginAs($this->admin);

        // Step 14: admin can read the findings.
        $this->get(route('reports.show', $report))->assertOk()
            ->assertSee('QA findings to review.')
            ->assertSee('QA recommendation.');

        // Step 15: Resolved -> Closed.
        $this->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'closed'])
            ->assertSessionHasNoErrors();
        $this->assertSame('closed', $report->fresh()->status);

        // The investigator can no longer act on a closed case.
        $this->loginAs($this->investigator);
        $this->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'investigating'])
            ->assertSessionHasErrors('status');
        $this->assertSame('closed', $report->fresh()->status);
    }

    // =================================================================
    // Invalid workflow: crafted status jumps
    // =================================================================

    public function test_invalid_submitted_to_closed(): void
    {
        $report = $this->makeReport(['status' => 'submitted']);

        $this->actingAs($this->admin)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'closed'])
            ->assertSessionHasErrors('status');

        $this->assertSame('submitted', $report->fresh()->status);
    }

    public function test_invalid_rejected_to_investigating(): void
    {
        $report = $this->makeReport(['status' => 'rejected']);

        $this->actingAs($this->admin)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'investigating'])
            ->assertSessionHasErrors('status');

        $this->assertSame('rejected', $report->fresh()->status);
    }

    public function test_invalid_closed_to_investigating(): void
    {
        $report = $this->makeReport(['status' => 'closed']);

        $this->actingAs($this->admin)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'investigating'])
            ->assertSessionHasErrors('status');

        $this->assertSame('closed', $report->fresh()->status);
    }

    public function test_invalid_assigned_to_resolved_skips_investigating(): void
    {
        $report = $this->makeReport(['status' => 'assigned']);
        Investigation::create([
            'report_id' => $report->id,
            'investigator_id' => $this->investigator->id,
            'findings' => 'Findings',
            'recommendations' => 'Recommendation',
        ]);

        $this->actingAs($this->investigator)
            ->from(route('reports.show', $report))
            ->patch(route('reports.update', $report), ['status' => 'resolved'])
            ->assertSessionHasErrors('status');

        $this->assertSame('assigned', $report->fresh()->status);
    }

    public function test_locked_statuses_cannot_be_changed_at_all(): void
    {
        foreach (['closed', 'rejected'] as $terminal) {
            $report = $this->makeReport(['status' => $terminal]);

            $this->actingAs($this->admin)
                ->from(route('reports.show', $report))
                ->patch(route('reports.update', $report), ['status' => 'under_review'])
                ->assertSessionHasErrors('status');

            $this->assertSame($terminal, $report->fresh()->status);
        }
    }

    // =================================================================
    // Invalid workflow: forbidden actors
    // =================================================================

    public function test_invalid_investigator_deleting_reports(): void
    {
        $report = $this->makeReport(['status' => 'assigned']);
        Investigation::create([
            'report_id' => $report->id,
            'investigator_id' => $this->investigator->id,
        ]);

        $this->actingAs($this->investigator)
            ->delete(route('reports.destroy', $report))
            ->assertForbidden();

        $this->assertDatabaseHas('reports', ['id' => $report->id, 'deleted_at' => null]);
    }

    public function test_invalid_reporter_accessing_investigation_management(): void
    {
        $report = $this->makeReport(['status' => 'under_review']);
        $this->assign($report, $this->investigator);
        $investigation = Investigation::where('report_id', $report->id)->firstOrFail();

        $this->actingAs($this->reporter);

        $this->get(route('investigations.index'))->assertForbidden();
        $this->get(route('investigations.show', $investigation))->assertForbidden();
        $this->get(route('investigations.create'))->assertForbidden();
        $this->get(route('reports.index'))->assertForbidden();
        $this->get(route('reports.show', $report))->assertForbidden();
    }

    public function test_reporter_cannot_edit_investigations(): void
    {
        $report = $this->makeReport([
            'status' => 'assigned',
            'user_id' => $this->reporter->id,
            'anonymous' => false,
        ]);
        $investigation = Investigation::create([
            'report_id' => $report->id,
            'investigator_id' => $this->investigator->id,
        ]);

        $this->actingAs($this->reporter)
            ->put(route('investigations.update', $investigation), [
                'findings' => 'Reporter should not write findings.',
            ])
            ->assertForbidden();

        $this->assertNull($investigation->fresh()->findings);
    }

    public function test_investigator_cannot_assign_investigators(): void
    {
        $report = $this->makeReport(['status' => 'under_review']);

        $this->actingAs($this->investigator)
            ->post(route('investigations.store'), [
                'report_id' => $report->id,
                'investigator_id' => $this->investigator->id,
            ])
            ->assertForbidden();

        $this->assertSame(0, Investigation::where('report_id', $report->id)->count());
    }

    public function test_investigator_cannot_close_cases(): void
    {
        $report = $this->makeReport(['status' => 'resolved']);
        $investigation = Investigation::create([
            'report_id' => $report->id,
            'investigator_id' => $this->investigator->id,
            'findings' => 'Findings',
            'recommendations' => 'Recommendation',
        ]);

        $this->actingAs($this->investigator)
            ->from(route('investigations.show', $investigation))
            ->post(route('investigations.close', $investigation))
            ->assertForbidden();

        $this->assertSame('resolved', $report->fresh()->status);
    }

    public function test_investigator_cannot_access_unassigned_case(): void
    {
        $report = $this->makeReport(['status' => 'investigating']);
        $investigation = Investigation::create([
            'report_id' => $report->id,
            'investigator_id' => $this->otherInvestigator->id,
        ]);

        $this->actingAs($this->investigator);

        $this->get(route('investigations.show', $investigation))->assertForbidden();
        $this->get(route('reports.show', $report))->assertForbidden();

        $this->put(route('investigations.update', $investigation), ['findings' => 'nope'])
            ->assertForbidden();
        $this->post(route('investigations.start', $investigation))->assertForbidden();
        $this->post(route('investigations.comments.store', $investigation), ['comment' => 'nope'])
            ->assertForbidden();
    }

    public function test_guest_cannot_access_investigations(): void
    {
        $report = $this->makeReport(['status' => 'assigned']);
        $investigation = Investigation::create([
            'report_id' => $report->id,
            'investigator_id' => $this->investigator->id,
        ]);

        $this->get(route('investigations.index'))->assertRedirect(route('login'));
        $this->get(route('investigations.show', $investigation))->assertRedirect(route('login'));
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    // =================================================================
    // Internal notes: only staff on the case
    // =================================================================

    public function test_only_assigned_investigator_or_admin_can_add_internal_notes(): void
    {
        $report = $this->makeReport(['status' => 'investigating']);
        $investigation = Investigation::create([
            'report_id' => $report->id,
            'investigator_id' => $this->investigator->id,
        ]);

        // Owner can comment.
        $this->actingAs($this->investigator)
            ->post(route('investigations.comments.store', $investigation), ['comment' => 'Owner note'])
            ->assertSessionHasNoErrors();
        $this->assertDatabaseHas('case_comments', ['report_id' => $report->id, 'comment' => 'Owner note']);

        // Admin can comment.
        $this->actingAs($this->admin)
            ->post(route('investigations.comments.store', $investigation), ['comment' => 'Admin note'])
            ->assertSessionHasNoErrors();

        // Another investigator cannot.
        $this->actingAs($this->otherInvestigator)
            ->post(route('investigations.comments.store', $investigation), ['comment' => 'Intruder note'])
            ->assertForbidden();
        $this->assertDatabaseMissing('case_comments', ['comment' => 'Intruder note']);
    }

    // =================================================================
    // Unassign returns the report to Under Review
    // =================================================================

    public function test_unassign_returns_report_to_under_review(): void
    {
        $report = $this->makeReport(['status' => 'under_review']);
        $this->assign($report, $this->investigator);
        $investigation = Investigation::where('report_id', $report->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->delete(route('investigations.destroy', $investigation))
            ->assertRedirect(route('investigations.index'));

        $this->assertSame('under_review', $report->fresh()->status);
        $this->assertDatabaseMissing('investigations', ['id' => $investigation->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'investigation.unassigned']);
    }

    // =================================================================
    // Full sweep: every audit-log artifact exists at the end
    // =================================================================

    public function test_closure_produces_a_complete_audit_trail(): void
    {
        $report = $this->makeReport(['status' => 'under_review']);
        $this->assign($report, $this->investigator);
        $investigation = Investigation::where('report_id', $report->id)->firstOrFail();

        $this->actingAs($this->investigator);
        $this->put(route('investigations.update', $investigation), [
            'findings' => 'Complete findings.',
            'recommendations' => 'Complete recommendation.',
        ]);
        $this->post(route('investigations.start', $investigation));
        $this->post(route('investigations.resolve', $investigation));

        $this->actingAs($this->admin);
        $this->patch(route('reports.update', $report), ['status' => 'closed']);

        $this->assertSame('closed', $report->fresh()->status);

        foreach (['investigation.assigned', 'investigation.updated', 'report.status_changed'] as $action) {
            $this->assertTrue(
                AuditLog::where('action', $action)->exists(),
                "Missing audit action: {$action}"
            );
        }
    }
}
