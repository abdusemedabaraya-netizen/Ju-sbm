<?php

namespace App\View\Composers;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

/**
 * Shared scaffolding for the sidebar count-badge composers.
 *
 * Both composers need the same role gate, the same role-scoped cache key and
 * the same "never cache inside tests" escape hatch; only the grouped query and
 * the resulting shape differ. Those differences live in the subclasses.
 */
abstract class CachedCountsComposer
{
    /** Seconds the grouped count query is cached for. */
    protected const TTL = 30;

    /** The view variable the counts are exposed as (e.g. "reportCounts"). */
    abstract protected function variable(): string;

    /** Cache key prefix (e.g. "jiacrs.report_counts"). */
    abstract protected function cacheKeyPrefix(): string;

    /** Zeroed counts, so views never deal with a missing variable. */
    abstract protected function emptyCounts(): array;

    /** Run the grouped query and build the counts array. */
    abstract protected function query(User $user): array;

    public function compose(View $view): void
    {
        $user = auth()->user();

        $view->with(
            $this->variable(),
            $user && $user->isStaff() ? $this->counts($user) : $this->emptyCounts()
        );
    }

    private function counts(User $user): array
    {
        $scope = $user->isAdmin() ? 'admin' : 'investigator';
        $key = $this->cacheKeyPrefix() . ".{$user->id}.{$scope}";

        // Tests mutate the database between requests and expect fresh numbers.
        if (app()->runningUnitTests()) {
            return $this->query($user);
        }

        return Cache::remember($key, now()->addSeconds(static::TTL), fn () => $this->query($user));
    }
}
