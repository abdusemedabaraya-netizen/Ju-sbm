<?php

namespace App\Providers;

use App\Listeners\AuthAuditSubscriber;
use App\Models\Investigation;
use App\Models\Report;
use App\Policies\InvestigationPolicy;
use App\Policies\ReportPolicy;
use App\Services\SiteSettings;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();               // Bootstrap pagination links
        Gate::policy(Report::class, ReportPolicy::class); // role-scoped report access
        Gate::policy(Investigation::class, InvestigationPolicy::class); // case access (BR-08/09)
        try { Event::subscribe(AuthAuditSubscriber::class); } catch (\Throwable $e) {} // login / logout / failed login audit

        try {
            SiteSettings::applyToConfig();            // saved Settings -> config('jiacrs.*')
        } catch (\Throwable $e) {
            // settings table or cache table not migrated yet: keep config defaults
        }
    }
}
