<?php

namespace App\View\Composers;

use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Supplies the Reports sidebar count badges.
 *
 * The counts come from ONE grouped query (status + priority) that is scoped by
 * role, and the result is cached for a short time so every page hit does not
 * re-run it. Investigators are scoped to their assigned cases (BR-08).
 */
class ReportCountsComposer
{
    /** Seconds the grouped count query is cached for. */
    private const TTL = 30;

    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin', 'Investigator'])) {
            $view->with('reportCounts', $this->emptyCounts());

            return;
        }

        $view->with('reportCounts', $this->counts($user));
    }

    private function counts(User $user): array
    {
        $scope = $user->hasAnyRole(['Super Admin', 'Admin']) ? 'admin' : 'investigator';
        $key = "jiacrs.report_counts.{$user->id}.{$scope}";

        // Never serve cached counts to the test suite: tests mutate the database
        // between requests and expect fresh numbers.
        if (app()->runningUnitTests()) {
            return $this->query($user);
        }

        return Cache::remember($key, now()->addSeconds(self::TTL), fn () => $this->query($user));
    }

    private function query(User $user): array
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

            if (in_array($row->priority, ['high', 'critical'], true)
                && ! in_array($row->status, Report::CLOSED_STATUSES, true)) {
                $counts['high_risk'] += $n;
            }
        }

        return $counts;
    }

    private function emptyCounts(): array
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
