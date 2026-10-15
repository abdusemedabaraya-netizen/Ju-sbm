<?php

namespace App\Providers;

use App\View\Composers\InvestigationCountsComposer;
use App\View\Composers\ReportCountsComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Count badges for the Reports link and the report page presets.
        View::composer(['layouts.partials.sidebar', 'reports.index'], ReportCountsComposer::class);

        // Count badges for the Investigations submenu (also reused by the tabs).
        View::composer(['layouts.partials.sidebar', 'investigations.index'], InvestigationCountsComposer::class);
    }
}
