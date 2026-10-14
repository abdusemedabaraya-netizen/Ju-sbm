<?php

namespace App\Http\Controllers;

use App\Models\Report;

class MyReportController extends Controller
{
    public function index()
    {
        // Anonymous reports have no user_id, so they can never appear here.
        $reports = Report::where('user_id', auth()->id())->latest()->paginate(10);

        return view('my-reports.index', compact('reports'));
    }

    public function show(Report $report)
    {
        abort_unless($report->user_id === auth()->id(), 404);

        // Staff-only fields (the internal status-change note and the acting
        // user) are never loaded for a reporter.
        $report->load(
            'department',
            'statusHistories:id,report_id,from_status,to_status,created_at'
        );

        return view('my-reports.show', compact('report'));
    }
}