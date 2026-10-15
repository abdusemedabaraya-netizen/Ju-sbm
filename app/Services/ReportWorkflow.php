<?php

namespace App\Services;

use App\Models\Investigation;
use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\User;
use App\Notifications\NewReportSubmitted;
use App\Notifications\ReportAssigned;
use App\Notifications\ReportStatusChanged;
use App\Notifications\ReportSubmittedConfirmation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Business rules BR-05, BR-06 and BR-07.
 *
 *   Submitted -> Under Review -> Assigned -> Investigating -> Resolved -> Closed
 *                     \-> Rejected
 *
 * "Assigned" is reached only through assign() (Admin / Super Admin, status Under Review).
 */
class ReportWorkflow
{
    public const TRANSITIONS = [
        'submitted'     => ['under_review'],
        'under_review'  => ['rejected'],      // or assign() -> assigned
        'assigned'      => ['investigating'],
        'investigating' => ['resolved'],
        'resolved'      => ['closed'],        // admin approves the resolution
        'closed'        => [],
        'rejected'      => [],
    ];

    /** The only moves the assigned investigator may make. */
    private const INVESTIGATOR = [
        'assigned'      => ['investigating'],
        'investigating' => ['resolved'],
    ];

    public static function allowedNext(Report $report, ?User $user): array
    {
        if (! $user) {
            return [];
        }

        if ($user->isAdmin()) {
            // Admins can move to available transitions in general
            return self::TRANSITIONS[$report->status] ?? [];
        }

        if ($user->isInvestigator() && $report->investigation?->investigator_id === $user->id) {
            return self::INVESTIGATOR[$report->status] ?? [];
        }

        return [];
    }

    public function changeStatus(Report $report, string $to, User $actor, ?string $note = null): void
    {
        if (! in_array($to, self::allowedNext($report, $actor), true)) {
            throw ValidationException::withMessages([
                'status' => 'That status change is not allowed from the current status.',
            ]);
        }

        // BR-09: a case can never be resolved before the assigned investigator
        // has recorded both findings and recommendations. Enforced here so that
        // every entry point (case workspace, report page, or a crafted request)
        // obeys the same rule.
        if ($to === 'resolved' && $report->investigation
            && (blank($report->investigation->findings) || blank($report->investigation->recommendations))) {
            throw ValidationException::withMessages([
                'status' => 'Record findings and recommendations before marking the case resolved.',
            ]);
        }

        $from = $report->status;

        DB::transaction(function () use ($report, $from, $to, $actor, $note) {
            $report->update(['status' => $to]);
            $this->record($report, $from, $to, $actor->id, $note);
            AuditLogger::log('report.status_changed', $report, "{$from} -> {$to}");
        });

        // Only registered, non-anonymous reporters have a user_id to notify.
        $this->safeNotify($report->user, new ReportStatusChanged($report, $from, $to));
    }

    public function changePriority(Report $report, string $priority, User $actor): void
    {
        $old = $report->priority;
        if ($old === $priority) {
            return;
        }

        $report->update(['priority' => $priority]);
        AuditLogger::log('report.priority_changed', $report, "{$old} -> {$priority}");
    }

    /** BR-07: only while Under Review; the status then becomes Assigned. */
    public function assign(Report $report, User $investigator, User $actor): Investigation
    {
        if ($report->status !== 'under_review') {
            throw ValidationException::withMessages([
                'report_id' => 'An investigator can only be assigned while the report is Under Review.',
            ]);
        }

        // A reporter must never be put in charge of their own case.
        if ($report->user_id && $report->user_id === $investigator->id) {
            throw ValidationException::withMessages([
                'investigator_id' => 'An investigator cannot be assigned to a case they reported.',
            ]);
        }

        $investigation = DB::transaction(function () use ($report, $investigator, $actor) {
            $investigation = Investigation::create([
                'report_id'       => $report->id,
                'investigator_id' => $investigator->id,
            ]);

            $report->update(['status' => 'assigned']);
            $this->record($report, 'under_review', 'assigned', $actor->id, "Assigned to {$investigator->name}");
            AuditLogger::log('investigation.assigned', $report, "Investigator user #{$investigator->id}");

            return $investigation;
        });

        $this->safeNotify($investigator, new ReportAssigned($report));

        return $investigation;
    }

    public function unassign(Investigation $investigation, User $actor): void
    {
        $report = $investigation->report;

        DB::transaction(function () use ($investigation, $report, $actor) {
            if (in_array($report->status, Report::ACTIVE_STATUSES, true)) {
                $from = $report->status;
                $report->update(['status' => 'under_review']);
                $this->record($report, $from, 'under_review', $actor->id, 'Assignment removed');
            }

            AuditLogger::log('investigation.unassigned', $report, "Investigator user #{$investigation->investigator_id}");
            $investigation->delete();
        });
    }

    /** First history row + audit entry. No IP, and no user id for anonymous reports. */
    public function recordSubmission(Report $report): void
    {
        $this->record($report, null, 'submitted', null, null);
        AuditLogger::record('report.submitted', $report->user_id, $report, null, false);
    }

    /** Admins are told about the new report; a registered reporter gets a confirmation. */
    public function notifyNewReport(Report $report): void
    {
        $this->safeNotify(User::role(User::ADMIN_ROLES)->get(), new NewReportSubmitted($report));

        if ($report->user_id && $report->user) {
            $this->safeNotify($report->user, new ReportSubmittedConfirmation($report));
        }
    }

    private function record(Report $report, ?string $from, string $to, ?int $by, ?string $note): void
    {
        ReportStatusHistory::create([
            'report_id'   => $report->id,
            'from_status' => $from,
            'to_status'   => $to,
            'changed_by'  => $by,
            'note'        => $note,
        ]);
    }

    private function safeNotify($notifiable, $notification): void
    {
        if (! $notifiable || (is_countable($notifiable) && count($notifiable) === 0)) {
            return;
        }

        try {
            Notification::send($notifiable, $notification);
        } catch (\Throwable $e) {
            report($e); // e.g. SMTP down: do not fail the whole request
        }
    }
}
