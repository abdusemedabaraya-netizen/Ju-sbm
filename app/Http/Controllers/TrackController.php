<?php

namespace App\Http\Controllers;

use App\Http\Requests\TrackReportRequest;
use App\Services\ReportTracker;
use Illuminate\Http\Request;

/**
 * Anonymous report tracking. The public page and the in-dashboard page share
 * this controller; only the rendered view differs (resolved by route name).
 */
class TrackController extends Controller
{
    /** Route name => form view. */
    private const FORM_VIEWS = [
        'my.reports.track.index' => 'my-reports.track',
    ];

    /** Route name => result view. */
    private const RESULT_VIEWS = [
        'my.reports.track.search' => 'my-reports.track-result',
    ];

    public function index(Request $request)
    {
        return view(self::FORM_VIEWS[$request->route()->getName()] ?? 'public.track');
    }

    public function search(TrackReportRequest $request, ReportTracker $tracker)
    {
        $report = $tracker->find($request->validated('tracking_number'), $request->validated('access_code'));

        // Same message for "not found" and "wrong code" so nobody can probe numbers.
        if (! $report) {
            return back()->withInput($request->only('tracking_number'))
                ->withErrors(['tracking_number' => 'No report matches that tracking number and access code.']);
        }

        // Pass only what the reporter may see.
        return view(self::RESULT_VIEWS[$request->route()->getName()] ?? 'public.track-result', [
            'report'  => $report->only(['tracking_number', 'category', 'status', 'created_at', 'updated_at']),
            'history' => $report->statusHistories()->get(['to_status', 'created_at']),
        ]);
    }
}
