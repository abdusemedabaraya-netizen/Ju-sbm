<?php

namespace App\Policies;

use App\Models\Investigation;
use App\Models\User;

/**
 * Access rules for a case (investigation).
 *
 * BR-08: an investigator may only open cases assigned to them.
 * BR-09: only the assigned investigator records findings and recommendations.
 *
 * The individual transition guards (start / resolve / close) also check the
 * report status so a stale button or a crafted request cannot skip a step.
 */
class InvestigationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin', 'Investigator']);
    }

    public function view(User $user, Investigation $investigation): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin'])
            || $investigation->investigator_id === $user->id;
    }

    /** Assigning is an admin-only action. */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin']);
    }

    /** BR-09: findings and recommendations belong to the assigned investigator. */
    public function update(User $user, Investigation $investigation): bool
    {
        return $investigation->investigator_id === $user->id;
    }

    public function delete(User $user, Investigation $investigation): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin']);
    }

    /** Internal case notes: staff on the case (admins or the owner). */
    public function comment(User $user, Investigation $investigation): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin'])
            || $investigation->investigator_id === $user->id;
    }

    /** Assigned investigator moves assigned -> investigating. */
    public function start(User $user, Investigation $investigation): bool
    {
        return $investigation->investigator_id === $user->id
            && $investigation->report?->status === 'assigned';
    }

    /** Assigned investigator moves investigating -> resolved. */
    public function resolve(User $user, Investigation $investigation): bool
    {
        return $investigation->investigator_id === $user->id
            && $investigation->report?->status === 'investigating';
    }

    /** Admin closes a resolved case: resolved -> closed. */
    public function close(User $user, Investigation $investigation): bool
    {
        return $user->hasAnyRole(['Super Admin', 'Admin'])
            && $investigation->report?->status === 'resolved';
    }

    /** Evidence on the case workspace is uploaded by the assigned investigator. */
    public function uploadEvidence(User $user, Investigation $investigation): bool
    {
        return $investigation->investigator_id === $user->id;
    }
}
