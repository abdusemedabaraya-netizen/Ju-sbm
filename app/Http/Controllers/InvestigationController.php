<?php

namespace App\Http\Controllers;

use App\Models\Investigation;
use App\Models\Report;
use App\Models\User;
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

        return view('cases.index', compact('investigations'));
    }

    public function create()
    {
        $this->adminOnly();

        $reports = Report::doesntHave('investigation')
            ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
            ->get(['id', 'tracking_number', 'subject']);

        $investigators = User::role('Investigator')->get(['id', 'name']);

        return view('cases.create', compact('reports', 'investigators'));
    }

    public function store(Request $request)
    {
        $this->adminOnly();

        $data = $request->validate([
            'report_id' => [
                'required',
                Rule::exists('reports', 'id')->whereNull('deleted_at'),
                Rule::unique('investigations', 'report_id'),   // one investigation per report
            ],
            'investigator_id' => ['required', Rule::exists('users', 'id')],
        ]);

        $investigator = User::role('Investigator')->findOrFail($data['investigator_id']);
        $report       = Report::findOrFail($data['report_id']);

        // Conflict of interest: cannot investigate a report you submitted.
        if ($report->user_id === $investigator->id) {
            return back()->withInput()->withErrors([
                'investigator_id' => 'An investigator cannot handle a report they submitted.',
            ]);
        }

        app(ReportWorkflow::class)->assign($report, $investigator, auth()->user());

        return redirect()->route('investigations.index')->with('success', 'Investigator assigned.');
    }

    public function show(Investigation $investigation)
    {
        $this->authorizeOwn($investigation);

        $investigation->load(['report', 'investigator:id,name']);

        return view('cases.show', compact('investigation'));
    }

    public function edit(Investigation $investigation)
    {
        $this->authorizeOwn($investigation);

        return view('cases.edit', compact('investigation'));
    }

    public function update(Request $request, Investigation $investigation)
    {
        $this->authorizeOwn($investigation);

        $investigation->update($request->validate([
            'findings'        => ['nullable', 'string', 'max:20000'],
            'recommendation'  => ['nullable', 'string', 'max:20000'],
        ]));

        return back()->with('success', 'Investigation updated.');
    }

    public function destroy(Investigation $investigation)
    {
        $this->adminOnly();

        app(ReportWorkflow::class)->unassign($investigation, auth()->user());

        return back()->with('success', 'Assignment removed.');
    }

    public function switchStatus(Request $request, Investigation $investigation)
    {
        $this->authorizeOwn($investigation);

        $report = $investigation->report;
        $move   = $request->input('move');

        $to = match ($move) {
            'start'   => 'investigating',
            'resolve' => 'resolved',
            default   => abort(422),
        };

        abort_unless(
            in_array($to, ReportWorkflow::allowedNext($report, auth()->user()), true),
            403
        );

        app(ReportWorkflow::class)->changeStatus($report, $to, auth()->user());

        $investigation->update(match ($to) {
            'investigating' => ['status' => 'ongoing'],
            'resolved'      => ['status' => 'completed', 'investigation_date' => now()->toDateString()],
        });

        return back()->with('success', 'Report status updated to ' . ucfirst($to) . '.');
    }

    private function scoped()
    {
        $query = Investigation::query();

        if (! $this->isAdmin()) {
            $query->where('investigator_id', auth()->id());
        }

        return $query;
    }

    private function authorizeOwn(Investigation $investigation): void
    {
        abort_unless($this->isAdmin() || $investigation->investigator_id === auth()->id(), 403);
    }
}

