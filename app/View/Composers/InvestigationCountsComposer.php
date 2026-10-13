<?php

namespace App\View\Composers;

use App\Models\Investigation;
use App\Models\Report;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Supplies the Investigations sidebar count badges.
 *
 * Counts are scoped by role (BR-08): an investigator only sees their own case
 * numbers, admins see everything. The case totals come from ONE grouped query
 * and the result is cached for a short time.
 */
class InvestigationCountsComposer
{
    private const TTL = 30;

    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin', 'Investigator'])) {
            $view->with('caseCounts', $this->emptyCounts());

            return;
        }

        $view->with('caseCounts', $this->counts($user));
    }

    private function counts(User $user): array
    {
        $scope = $user->hasAnyRole(['Super Admin', 'Admin']) ? 'admin' : 'investigator';
        $key = "jiacrs.case_counts.{$user->id}.{$scope}";

        // Tests mutate the database between requests and expect fresh numbers.
        if (app()->runningUnitTests()) {
            return $this->query($user);
        }

        return Cache::remember($key, now()->addSeconds(self::TTL), fn () => $this->query($user));
    }

    private function query(User $user): array
    {
        $isAdmin = $user->hasAnyRole(['Super Admin', 'Admin']);

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
        $counts['unassigned'] = $isAdmin
            ? Report::doesntHave('investigation')->where('status', 'under_review')->count()
            : 0;

        return $counts;
    }

    private function emptyCounts(): array
    {
        return [
            'total' => 0,
            'active' => 0,
            'completed' => 0,
            'unassigned' => 0,
        ];
    }
}
