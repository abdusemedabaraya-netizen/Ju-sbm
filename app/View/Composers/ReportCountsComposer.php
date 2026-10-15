<?php

namespace App\View\Composers;

use App\Models\Report;
use App\Models\User;

/**
 * Supplies the Reports sidebar count badges.
 *
 * The counts come from ONE grouped query (status + priority) that is scoped by
 * role (BR-08), shared and cached through the base composer.
 */
class ReportCountsComposer extends CachedCountsComposer
{
    protected function variable(): string
    {
        return 'reportCounts';
    }

    protected function cacheKeyPrefix(): string
    {
        return 'jiacrs.report_counts';
    }

    protected function query(User $user): array
    {
        $rows = Report::query()
            ->visibleTo($user)
            ->selectRaw('status, priority, COUNT(*) as aggregate')
            ->groupBy('status', 'priority')
            ->get();

        $counts = $this->emptyCounts();

        foreach ($rows as $row) {
            $n = (int) $row->aggregate;
            $counts['all'] += $n;

            match ($row->status) {
                'submitted' => $counts['new'] += $n,
                'under_review' => $counts['under_review'] += $n,
                'assigned', 'investigating' => $counts['in_progress'] += $n,
                'resolved', 'closed' => $counts['resolved_closed'] += $n,
                'rejected' => $counts['rejected'] += $n,
                default => null,
            };

            if (in_array($row->priority, Report::HIGH_RISK_PRIORITIES, true)
                && ! in_array($row->status, Report::CLOSED_STATUSES, true)) {
                $counts['high_risk'] += $n;
            }
        }

        return $counts;
    }

    protected function emptyCounts(): array
    {
        return [
            'all' => 0,
            'new' => 0,
            'under_review' => 0,
            'in_progress' => 0,
            'high_risk' => 0,
            'resolved_closed' => 0,
            'rejected' => 0,
        ];
    }
}
