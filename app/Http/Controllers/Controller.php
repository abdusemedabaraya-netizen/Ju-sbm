<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    protected function isAdmin(): bool
    {
        return auth()->user()?->hasAnyRole(['Admin', 'Super Admin']) ?? false;
    }

    protected function adminOnly(): void
    {
        abort_unless($this->isAdmin(), 403);
    }

    /** ReportPolicy is the single source of truth for who may open a report. */
    protected function canAccessReport(Report $report): bool
    {
        return auth()->user()?->can('view', $report) ?? false;
    }
}
