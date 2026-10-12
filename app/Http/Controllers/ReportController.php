<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    private const STATUSES   = ['submitted', 'under_review', 'assigned', 'investigating', 'resolved', 'closed', 'rejected'];
    private const PRIORITIES = ['low', 'medium', 'high', 'critical'];
    private const CLOSED     = ['resolved', 'closed', 'rejected'];

    public function index(Request $request)
    {
        $reports = $this->visibleReports()
            ->with('department')                       // never load the reporter here
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->priority === 'high', fn ($q) => $q->whereIn('priority', ['high', 'critical']))
            ->when($request->priority && $request->priority !== 'high',
                fn ($q) => $q->where('priority', $request->priority))
            ->when($request->state === 'open', fn ($q) => $q->whereNotIn('status', self::CLOSED))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('tracking_number', 'like', "%{$s}%")
                ->orWhere('subject', 'like', "%{$s}%")))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('reports.index', compact('reports'));
    }

    public function show(Report $report)
    {
        $this->authorizeAccess($report);

        $report->load(['department', 'evidences', 'investigation.investigator']);

        // Protect anonymous reporters: never expose the account behind the report.
        if ($report->anonymous) {
            $report->setRelation('user', null);
        } else {
            $report->load('user');
        }

        return view('reports.show', compact('report'));
    }

    public function update(Request $request, Report $report)
    {
        $this->authorizeAccess($report);

        // Investigators may only move their own case between these two states.
        $statuses = $this->isAdmin() ? self::STATUSES : ['investigating', 'resolved'];

        $rules = ['status' => ['sometimes', Rule::in($statuses)]];
        if ($this->isAdmin()) {
            $rules['priority'] = ['sometimes', Rule::in(self::PRIORITIES)];
        }

        $report->update($request->validate($rules));

        return back()->with('success', 'Report updated successfully.');
    }

    public function destroy(Report $report)
    {
        $this->adminOnly();

        $report->delete(); // soft delete

        return redirect()->route('reports.index')->with('success', 'Report deleted.');
    }

    /** Admins see everything; investigators only reports assigned to them. */
    private function visibleReports()
    {
        $query = Report::query();

        if (! $this->isAdmin()) {
            $query->whereHas('investigation', fn ($q) => $q->where('investigator_id', auth()->id()));
        }

        return $query;
    }

    private function authorizeAccess(Report $report): void
    {
        abort_unless($this->visibleReports()->whereKey($report->id)->exists(), 403);
    }
}
