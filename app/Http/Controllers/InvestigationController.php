<?php

namespace App\Http\Controllers;

use App\Models\Investigation;
use App\Models\Report;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReportWorkflow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvestigationController extends Controller
{
    public function index()
    {
        $investigations = $this->scoped()
            ->with(['report:id,tracking_number,subject,status', 'investigator:id,name'])
            ->latest()
            ->paginate(10);

        return view('investigations.index', compact('investigations'));
    }

    public function create()
    {
        $this->adminOnly();

        // BR-07: only reports that are Under Review and not yet assigned.
        $reports = Report::doesntHave('investigation')
            ->where('status', 'under_review')
            ->get(['id', 'tracking_number', 'subject']);

        $investigators = User::role('Investigator')->get(['id', 'name']);

        return view('investigations.create', compact('reports', 'investigators'));
    }

    public function store(Request $request, ReportWorkflow $workflow)
    {
        $this->adminOnly();

        $data = $request->validate([
            'report_id' => [
                'required',
                Rule::exists('reports', 'id')->whereNull('deleted_at'),
                Rule::unique('investigations', 'report_id'),
            ],
            'investigator_id' => ['required', Rule::exists('users', 'id')],
        ]);

        $investigator = User::role('Investigator')->findOrFail($data['investigator_id']);
        $report       = Report::findOrFail($data['report_id']);

        // Conflict of interest: nobody investigates a report they submitted themselves.
        if ($report->user_id === $investigator->id) {
            return back()->withInput()->withErrors([
                'investigator_id' => 'An investigator cannot handle a report they submitted.',
            ]);
        }

        $workflow->assign($report, $investigator, $request->user()); // checks "Under Review"

        return redirect()->route('reports.show', $report)->with('success', 'Investigator assigned.');
    }

    public function show(Investigation $investigation)
    {
        $this->authorizeView($investigation);

        $investigation->load(['report.department', 'investigator:id,name']);

        return view('investigations.show', compact('investigation'));
    }

    /** BR-09: only the assigned investigator records findings and recommendations. */
    public function update(Request $request, Investigation $investigation)
    {
        abort_unless($investigation->investigator_id === auth()->id(), 403);

        $investigation->update($request->validate([
            'findings'        => ['nullable', 'string', 'max:20000'],
            'recommendations' => ['nullable', 'string', 'max:20000'],
        ]));

        AuditLogger::log('investigation.updated', $investigation->report);

        return back()->with('success', 'Investigation updated.');
    }

    public function destroy(Investigation $investigation, ReportWorkflow $workflow)
    {
        $this->adminOnly();

        $workflow->unassign($investigation, auth()->user());

        return redirect()->route('investigations.index')->with('success', 'Assignment removed.');
    }

    private function scoped()
    {
        $query = Investigation::query();

        if (! $this->isAdmin()) {
            $query->where('investigator_id', auth()->id());
        }

        return $query;
    }

    private function authorizeView(Investigation $investigation): void
    {
        abort_unless($this->isAdmin() || $investigation->investigator_id === auth()->id(), 403);
    }
}
