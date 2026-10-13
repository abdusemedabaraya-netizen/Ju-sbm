<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InvestigationController;
use App\Http\Controllers\MyReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SubmitReportController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public: landing page
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => view('home.index'))->name('home');

/*
|--------------------------------------------------------------------------
| Public: report submission (the real form)
|--------------------------------------------------------------------------
*/

Route::get('/report', [SubmitReportController::class, 'create'])->name('report.create');

Route::post('/report', [SubmitReportController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('report.store');

Route::get('/report/submitted', [SubmitReportController::class, 'submitted'])->name('report.submitted');

/*
|--------------------------------------------------------------------------
| Public: tracking (tracking number + access code)
|--------------------------------------------------------------------------
*/

Route::get('/track', [TrackController::class, 'index'])->name('track.index');

Route::post('/track', [TrackController::class, 'search'])
    ->middleware('throttle:10,1')
    ->name('track.search');

/*
|--------------------------------------------------------------------------
| Old landing-page links: same route names, now sent to the real pages
|--------------------------------------------------------------------------
*/

Route::redirect('/report/create', '/report')->name('reports.create');
Route::redirect('/report/anonymous', '/report')->name('reports.anonymous');
Route::redirect('/report/track', '/track')->name('reports.track');

Route::post('/report/track', [TrackController::class, 'search'])
    ->middleware('throttle:10,1')
    ->name('reports.track.search');

/*
|--------------------------------------------------------------------------
| After login
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {
    if (auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Investigator'])) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('my.reports.index');
})->middleware(['auth', 'verified'])->name('dashboard');

/*
|--------------------------------------------------------------------------
| Any logged-in user: profile, my reports, notifications
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/profile/photo/{user}', [ProfilePhotoController::class, 'show'])->name('profile.photo');
    Route::post('/profile/photo', [ProfilePhotoController::class, 'store'])->name('profile.photo.store');
    Route::delete('/profile/photo', [ProfilePhotoController::class, 'destroy'])->name('profile.photo.destroy');

    Route::get('/my/reports', [MyReportController::class, 'index'])->name('my.reports.index');
    Route::get('/my/reports/{report}', [MyReportController::class, 'show'])->name('my.reports.show');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');
});

/*
|--------------------------------------------------------------------------
| Staff: Admin, Super Admin, Investigator
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:Super Admin|Admin|Investigator'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        Route::resource('reports', ReportController::class)
            ->only(['index', 'show', 'update', 'destroy']);

        // BR-07: assign an investigator from the report page (Admin / Super Admin,
        // only while Under Review). Existing route names stay intact.
        Route::post('reports/{report}/assign', [ReportController::class, 'assign'])->name('reports.assign');

        Route::post('reports/{report}/evidence', [EvidenceController::class, 'store'])->name('evidence.store');
        Route::get('evidence/{evidence}/download', [EvidenceController::class, 'download'])->name('evidence.download');

        Route::resource('investigations', InvestigationController::class)
            ->except(['edit']);

        // Case workspace transitions and internal notes (existing route names kept).
        Route::post('investigations/{investigation}/start', [InvestigationController::class, 'start'])->name('investigations.start');
        Route::post('investigations/{investigation}/resolve', [InvestigationController::class, 'resolve'])->name('investigations.resolve');
        Route::post('investigations/{investigation}/close', [InvestigationController::class, 'close'])->name('investigations.close');
        Route::post('investigations/{investigation}/comments', [InvestigationController::class, 'comment'])->name('investigations.comments.store');
    });

/*
|--------------------------------------------------------------------------
| Admin and Super Admin
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:Super Admin|Admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

        Route::get('exports', [ExportController::class, 'index'])->name('exports.index');
        Route::get('exports/csv', [ExportController::class, 'csv'])->name('exports.csv');
        Route::get('exports/print', [ExportController::class, 'print'])->name('exports.print');

        Route::resource('departments', DepartmentController::class)->except(['show']);
        Route::resource('categories', CategoryController::class)->except(['show']);
        Route::resource('users', UserController::class)->only(['index', 'show']);
    });

/*
|--------------------------------------------------------------------------
| Super Admin only
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:Super Admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('roles/staff', [RoleController::class, 'storeStaff'])->name('roles.staff.store');
        Route::patch('roles/users/{user}', [RoleController::class, 'assign'])->name('roles.assign');

        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
    });

/*
|--------------------------------------------------------------------------
| Authentication (Breeze)
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';