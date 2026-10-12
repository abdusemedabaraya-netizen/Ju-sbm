<?php

namespace App\Providers;

use App\Listeners\AuthAuditSubscriber;
use App\Services\SiteSettings;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
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
        Event::subscribe(AuthAuditSubscriber::class); // login / logout / failed login audit

        try {
            SiteSettings::applyToConfig();            // saved Settings -> config('jiacrs.*')
        } catch (\Throwable $e) {
            // settings table or cache table not migrated yet: keep config defaults
        }
    }
}
