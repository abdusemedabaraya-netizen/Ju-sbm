<?php

namespace App\View\Composers;

use App\Models\Investigation;
use App\Models\Report;
use App\Models\User;

/**
 * Supplies the Investigations sidebar count badges.
 *
 * Counts are scoped by role (BR-08): an investigator only sees their own case
 * numbers, admins see everything. The case totals come from ONE grouped query,
 * shared and cached through the base composer.
 */
class InvestigationCountsComposer extends CachedCountsComposer
{
    protected function variable(): string
    {
        return 'caseCounts';
    }

    protected function cacheKeyPrefix(): string
    {
        return 'jiacrs.case_counts';
    }

    protected function query(User $user): array
    {
        $rows = Investigation::query()
            ->visibleTo($user)
            ->join('reports', 'reports.id', '=', 'investigations.report_id')
            ->whereNull('reports.deleted_at')
            ->selectRaw('reports.status as report_status, COUNT(*) as aggregate')
            ->groupBy('reports.status')
            ->get();

        $counts = $this->emptyCounts();

        foreach ($rows as $row) {
            $n = (int) $row->aggregate;
            $counts['total'] += $n;

            match ($row->report_status) {
                'assigned', 'investigating' => $counts['active'] += $n,
                'resolved', 'closed' => $counts['completed'] += $n,
                default => null,
            };
        }

        // "Assign Investigator" badge: reports Under Review with no case yet.
        $counts['unassigned'] = $user->isAdmin()
            ? Report::unassignedUnderReview()->count()
            : 0;

        return $counts;
    }

    protected function emptyCounts(): array
    {
        return [
            'total' => 0,
            'active' => 0,
            'completed' => 0,
            'unassigned' => 0,
        ];
    }
}
