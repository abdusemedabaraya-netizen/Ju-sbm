<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Models\Category;
use App\Models\Department;
use App\Services\ReportIntake;
use App\Services\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Report submission. The public form and the in-dashboard form share this
 * controller; only the view and the "reporting closed" response differ.
 */
class SubmitReportController extends Controller
{
    public function create(Request $request)
    {
        SiteSettings::applyToConfig();

        if (! config('jiacrs.public_reporting_enabled')) {
            return $this->dashboard($request)
                ? redirect()->route('my.reports.index')->with('error', 'Public reporting is temporarily closed.')
                : view('public.report-closed');
        }

        return view(
            $this->dashboard($request) ? 'my-reports.submit' : 'public.report-create',
            [
                'departments' => Department::orderBy('name')->get(['id', 'name']),
                'categories'  => Category::active()->orderBy('sort_order')->get(['id', 'slug', 'name']),
            ]
        );
    }

    public function store(StoreReportRequest $request, ReportIntake $intake)
    {
        if (! config('jiacrs.public_reporting_enabled')) {
            throw ValidationException::withMessages(['subject' => 'Public reporting is temporarily closed.']);
        }

        // Guests are always anonymous; logged-in users choose.
        $userId = (! auth()->check() || $request->boolean('anonymous')) ? null : auth()->id();

        $result = $intake->submit($request->validated(), $request->file('files') ?? [], $userId);

        return redirect()
            ->route(auth()->check() ? 'my.reports.submitted' : 'report.submitted')
            ->with([
                'tracking_number' => $result['report']->tracking_number,
                'access_code'     => $result['access_code'],
            ]);
    }

    public function submitted(Request $request)
    {
        // The access code is only available right after submission.
        abort_unless(session()->has('access_code'), 404);

        return view(
            $this->dashboard($request) ? 'my-reports.submitted' : 'public.report-submitted',
            [
                'trackingNumber' => session('tracking_number'),
                'accessCode'     => session('access_code'),
            ]
        );
    }

    /** The dashboard variants live under the "my.reports.*" route names. */
    private function dashboard(Request $request): bool
    {
        return $request->routeIs('my.reports.*');
    }
}
