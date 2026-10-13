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
        // Count badges for the Reports submenu; only computed for the sidebar.
        View::composer('layouts.partials.sidebar', ReportCountsComposer::class);

        // Count badges for the Investigations submenu (also reused by the tabs).
        View::composer(['layouts.partials.sidebar', 'investigations.index'], InvestigationCountsComposer::class);
    }
}
