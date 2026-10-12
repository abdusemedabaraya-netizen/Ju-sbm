<?php

namespace App\Http\Controllers;

use App\Models\Report;

class MyReportController extends Controller
{
    public function index()
    {
        // Anonymous reports have no user_id, so they can never appear here.
        $reports = Report::where('user_id', auth()->id())->latest()->paginate(10);

        return view('reporter.index', compact('reports'));
    }

    public function show(Report $report)
    {
        abort_unless($report->user_id === auth()->id(), 404);

        $report->load('department', 'statusHistories');

       return view('reporter.show', compact('report'));
    }
}