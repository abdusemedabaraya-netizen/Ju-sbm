<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

/**
 * Single source of truth for who may do what with a report.
 *
 * BR-07: only Admin / Super Admin assign an investigator, and only while the
 * report is Under Review.
 * BR-08: investigators may only reach their assigned cases and may never
 * delete reports.
 * BR-10: only Admin changes the priority.
 */
class ReportPolicy
{
    /** Any staff member may open the reports section. */
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    /** Admins see everything; an investigator only their assigned case. */
    public function view(User $user, Report $report): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isInvestigator()
            && $report->investigation?->investigator_id === $user->id;
    }

    /** Status changes: admins may drive the flow, the assigned investigator may progress own case. */
    public function update(User $user, Report $report): bool
    {
        return $this->view($user, $report);
    }

    /** BR-10: priority is set automatically and only an Admin may override it. */
    public function changePriority(User $user, Report $report): bool
    {
        return $user->isAdmin();
    }

    /** BR-07: assign only while Under Review, and only by Admin / Super Admin. */
    public function assign(User $user, Report $report): bool
    {
        return $user->isAdmin()
            && $report->status === 'under_review';
    }

    /** Evidence may be added by anyone who can see the report. */
    public function uploadEvidence(User $user, Report $report): bool
    {
        return $this->view($user, $report);
    }

    /** BR-08 / BR-10: deleting a report is an admin-only soft delete. */
    public function delete(User $user, Report $report): bool
    {
        return $user->isAdmin();
    }
}
