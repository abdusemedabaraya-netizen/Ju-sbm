<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\InvestigationController;
use App\Http\Controllers\MyReportController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SubmitReportController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporterDashboardController;
use App\Http\Controllers\InvestigatorCaseController;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('home.index');
})->name('home');


// /*
// |--------------------------------------------------------------------------
// | Public Report Routes
// |--------------------------------------------------------------------------
// */

// Route::get('/report/create', function () {
//     return view('reports.create');
// })->name('reports.create');

// Route::get('/report/anonymous', function () {
//     return view('reports.anonymous');
// })->name('reports.anonymous');

// Route::get('/report/track', function () {
//     return view('reports.track');
// })->name('reports.track');


/*
|--------------------------------------------------------------------------
| Public Report Submission
|--------------------------------------------------------------------------
*/

Route::get('/report/anonymous', [SubmitReportController::class, 'create'])
    ->name('report.create.anonymous');
Route::get('/report/identified', [SubmitReportController::class, 'create'])
    ->middleware('auth')
    ->name('report.create.identified');

Route::post('/report', [SubmitReportController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('report.store');

Route::get('/report/submitted', [SubmitReportController::class, 'submitted'])
    ->name('report.submitted');

/*
|--------------------------------------------------------------------------
| Public Tracking
|--------------------------------------------------------------------------
*/

Route::get('/track', [TrackController::class, 'index'])
    ->name('track.index');

Route::post('/track', [TrackController::class, 'search'])
    ->middleware('throttle:10,1')
    ->name('track.search');


/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', function () {

    $user = auth()->user();

    if ($user->hasAnyRole([
        'Super Admin',
        'Admin',
        'Investigator'
    ])) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('reporter.dashboard');

})->middleware(['auth', 'verified'])->name('dashboard');


/*
|--------------------------------------------------------------------------
| Profile & User Area
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');


    // Reporter Dashboard
    Route::get('/my/dashboard', [ReporterDashboardController::class, 'index'])
        ->name('reporter.dashboard');


    // My Reports
    Route::get('/my/reports', [MyReportController::class, 'index'])
        ->name('my.reports.index');

    Route::get('/my/reports/{report}', [MyReportController::class, 'show'])
        ->name('my.reports.show');


    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.readAll');
});


/*
|--------------------------------------------------------------------------
| Staff: Admin, Super Admin, Investigator
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'role:Super Admin|Admin|Investigator'
])
    ->prefix('admin')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('admin.dashboard');

// My Assigned Cases (investigator)
        Route::get('/my-cases', [InvestigatorCaseController::class, 'index'])
            ->name('investigations.mine');
        // Reports
        Route::resource('reports', ReportController::class)
            ->only([
                'index',
                'show',
                'update',
                'destroy'
            ]);


        // Evidence
        Route::post(
            'reports/{report}/evidence',
            [EvidenceController::class, 'store']
        )->name('evidence.store');

        Route::get(
            'evidence/{evidence}/download',
            [EvidenceController::class, 'download']
        )->name('evidence.download');


        // Investigations
        Route::resource('investigations', InvestigationController::class)
            ->except(['edit']);

        Route::post(
            'investigations/{investigation}/status',
            [InvestigationController::class, 'switchStatus']
        )->name('investigations.status');
    });


/*
|--------------------------------------------------------------------------
| Admin and Super Admin
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'role:Super Admin|Admin'
])
    ->prefix('admin')
    ->group(function () {

        // Departments
        Route::resource('departments', DepartmentController::class)
            ->except(['show']);


        // Users
        Route::resource('users', UserController::class)
            ->only([
                'index',
                'show'
            ]);
    });


/*
|--------------------------------------------------------------------------
| Super Admin Only
|--------------------------------------------------------------------------
*/

Route::middleware([
    'auth',
    'verified',
    'role:Super Admin'
])
    ->prefix('admin')
    ->group(function () {

        Route::get(
            'audit-logs',
            [AuditLogController::class, 'index']
        )->name('audit-logs.index');
    });


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';