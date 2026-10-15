<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TrackController extends Controller
{
    public function index()
    {
        return view('public.track');
    }

    public function indexInDashboard()
    {
        return view('my-reports.track');
    }

    public function search(Request $request)
    {
        $data = $request->validate([
            'tracking_number' => ['required', 'string', 'max:50'],
            'access_code'     => ['required', 'string', 'max:50'],
        ]);

        $report = Report::where('tracking_number', trim($data['tracking_number']))->first();

        // Same message for "not found" and "wrong code" so nobody can probe numbers.
        if (! $report
            || ! $report->access_code_hash
            || ! Hash::check($this->normalizeAccessCode($data['access_code']), $report->access_code_hash)) {
            return back()->withInput($request->only('tracking_number'))
                ->withErrors(['tracking_number' => 'No report matches that tracking number and access code.']);
        }

        // Authenticated dashboard tracking uses the dashboard result view.
        $view = request()->routeIs('my.reports.track.search')
            ? 'my-reports.track-result'
            : 'public.track-result';

        // Pass only what the reporter may see.
        return view($view, [
            'report' => $report->only(['tracking_number', 'category', 'status', 'created_at', 'updated_at']),
            'history' => $report->statusHistories()->get(['to_status', 'created_at']),
        ]);
    }

    /**
     * Access codes are shown grouped for readability (e.g. "ESREK-DR8O1").
     * Strip separators and normalise casing before comparing against the hash.
     */
    private function normalizeAccessCode(string $code): string
    {
        return strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $code));
    }
}