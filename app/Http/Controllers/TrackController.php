<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\ReportStatusHistory;
class TrackController extends Controller
{
    public function index()
    {
        return view('public.track');
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
            || ! Hash::check(strtoupper(trim($data['access_code'])), $report->access_code_hash)) {
            return back()->withInput($request->only('tracking_number'))
                ->withErrors(['tracking_number' => 'No report matches that tracking number and access code.']);
        }

        // Pass only what the reporter may see.
$history = ReportStatusHistory::where('report_id', $report->id)
    ->orderBy('id')
    ->get();

return view('public.track-result', [
    'report'  => $report->only(['tracking_number', 'category', 'status', 'created_at', 'updated_at']),
    'history' => $history,
]);
    }
}