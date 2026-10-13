<?php

namespace App\Http\Controllers;

use App\Models\CaseComment;
use App\Models\Category;
use App\Models\Investigation;
use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReportWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class InvestigationController extends Controller
{
    private const ACTIVE = ['assigned', 'investigating'];
    private const DONE   = ['resolved', 'closed'];

    public function index(Request $request)
    {
        Gate::authorize('viewAny', Investigation::class);

        $f = $request->validate([
            'tab'             => ['nullable', Rule::in(['all', 'active', 'completed'])],
            'search'          => ['nullable', 'string', 'max:100'],
            'status'          => ['nullable', Rule::in(array_merge(self::ACTIVE, self::DONE))],
            'priority'        => ['nullable', Rule::in(['low', 'medium', 'high', 'critical'])],
            'investigator_id' => ['nullable', 'integer'],
        ]);

        $tab     = $f['tab'] ?? 'all';
        $isAdmin = $this->isAdmin();

        $investigations = $this->scoped()
            ->select('investigations.*')
            ->addSelect(['resolved_at' => ReportStatusHistory::select('created_at')
                ->whereColumn('report_id', 'investigations.report_id')
                ->where('to_status', 'resolved')
                ->orderByDesc('id')->limit(1)])
            ->withCasts(['resolved_at' => 'datetime'])
            ->with(['report:id,tracking_number,subject,status,priority,anonymous', 'investigator:id,name'])
            ->whereHas('report', function ($q) use ($tab, $f) {
                $q->when($tab === 'active', fn ($r) => $r->whereIn('status', self::ACTIVE))
                  ->when($tab === 'completed', fn ($r) => $r->whereIn('status', self::DONE))
                  ->when($f['status'] ?? null, fn ($r, $v) => $r->where('status', $v))
                  ->when($f['priority'] ?? null, fn ($r, $v) => $r->where('priority', $v))
                  ->when($f['search'] ?? null, fn ($r, $s) => $r->where(fn ($w) => $w
                      ->where('tracking_number', 'like', "%{$s}%")
                      ->orWhere('subject', 'like', "%{$s}%")));
            })
            ->when($isAdmin && ($f['investigator_id'] ?? null),
                fn ($q, $v) => $q->where('investigations.investigator_id', $v))
            ->latest('investigations.created_at')
            ->paginate(10)
            ->withQueryString();

        // Tab numbers (same scope as the list)
        $counts = DB::table('investigations as i')
            ->join('reports as r', 'r.id', '=', 'i.report_id')
            ->whereNull('r.deleted_at')
            ->when(! $isAdmin, fn ($q) => $q->where('i.investigator_id', auth()->id()))
            ->selectRaw("COUNT(*) as total,
                COALESCE(SUM(r.status IN ('assigned','investigating')), 0) as active,
                COALESCE(SUM(r.status IN ('resolved','closed')), 0) as completed")
            ->first();

        $investigators = $isAdmin
            ? User::role('Investigator')->orderBy('name')->get(['users.id', 'users.name'])
            : collect();

        return view('investigations.index', compact('investigations', 'tab', 'counts', 'investigators'));
    }

    /** BR-07: only reports that are Under Review and still without an investigator. */
    public function create()
    {
        Gate::authorize('create', Investigation::class);

        $reports = Report::doesntHave('investigation')
            ->where('status', 'under_review')
            ->orderBy('id')
            ->get(['id', 'tracking_number', 'subject', 'priority', 'user_id']);

        $investigators = User::role('Investigator')
            ->select(['users.id', 'users.name'])
            ->withCount(['investigations as open_cases' => fn ($q) => $q->whereHas(
                'report', fn ($r) => $r->whereIn('status', self::ACTIVE)
            )])
            ->orderBy('name')
            ->get();

        return view('investigations.create', compact('reports', 'investigators'));
    }

    public function store(Request $request, ReportWorkflow $workflow)
    {
        Gate::authorize('create', Investigation::class);

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

        // Checks "Under Review", sets "Assigned", writes history + audit, notifies the investigator.
        $workflow->assign($report, $investigator, $request->user());

        return redirect()->route('reports.show', $report)->with('success', 'Investigator assigned.');
    }

    public function show(Investigation $investigation)
    {
        Gate::authorize('view', $investigation);

        $investigation->load([
            'investigator:id,name',
            'report.department',
            'report.evidences',
            'report.statusHistories.changedBy:id,name',
            'report.comments.user:id,name',
        ]);

        $report = $investigation->report;
        abort_if(! $report, 404);

        $categoryName = Category::where('slug', $report->category)->value('name');

        $user = auth()->user();
        $flags = [
            'canEditFindings' => $user->can('update', $investigation),
            'canComment'      => $user->can('comment', $investigation),
            'canStart'        => $user->can('start', $investigation),
            'canResolve'      => $user->can('resolve', $investigation),
            'canClose'        => $user->can('close', $investigation),
            'canUpload'       => $user->can('uploadEvidence', $investigation),
            'canRemove'       => $user->can('delete', $investigation),
        ];

        return view('investigations.show', compact('investigation', 'report', 'flags', 'categoryName'));
    }

    /** BR-09: only the assigned investigator, and only while the case is open. */
    public function update(Request $request, Investigation $investigation)
    {
        Gate::authorize('update', $investigation);

        $investigation->update($request->validate([
            'findings'        => ['nullable', 'string', 'max:20000'],
            'recommendations' => ['nullable', 'string', 'max:20000'],
        ]));

        AuditLogger::log('investigation.updated', $investigation->report);

        return back()->with('success', 'Findings saved.');
    }

    public function start(Investigation $investigation, ReportWorkflow $workflow)
    {
        Gate::authorize('start', $investigation);
        abort_if(! $investigation->report, 404);

        $workflow->changeStatus($investigation->report, 'investigating', auth()->user());

        return back()->with('success', 'Investigation started.');
    }

    /** Marks an investigating case Resolved. Findings must already be recorded via the findings form. */
    public function resolve(Request $request, Investigation $investigation, ReportWorkflow $workflow)
    {
        Gate::authorize('resolve', $investigation);
        abort_if(! $investigation->report, 404);

        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);

        if (blank($investigation->findings) || blank($investigation->recommendations)) {
            return back()->withErrors([
                'findings' => 'Record findings and recommendations before marking the case resolved.',
            ]);
        }

        $workflow->changeStatus($investigation->report, 'resolved', $request->user(), $data['note'] ?? null);

        return redirect()->route('investigations.show', $investigation)
            ->with('success', 'Case marked as resolved. An admin will review it and close the case.');
    }

    /** Admin approves the resolution. */
    public function close(Request $request, Investigation $investigation, ReportWorkflow $workflow)
    {
        Gate::authorize('close', $investigation);
        abort_if(! $investigation->report, 404);

        $data = $request->validate(['note' => ['nullable', 'string', 'max:2000']]);

        $workflow->changeStatus($investigation->report, 'closed', $request->user(), $data['note'] ?? null);

        return back()->with('success', 'Case approved and closed.');
    }

    /** Internal case notes: never shown to the reporter. */
    public function comment(Request $request, Investigation $investigation)
    {
        Gate::authorize('comment', $investigation);
        abort_if(! $investigation->report, 404);

        $data = $request->validate(['comment' => ['required', 'string', 'max:5000']]);

        CaseComment::create([
            'report_id'   => $investigation->report_id,
            'user_id'     => $request->user()->id,
            'comment'     => $data['comment'],
            'is_internal' => true,
        ]);

        AuditLogger::log('case.note_added', $investigation->report);

        return back()->with('success', 'Note added.');
    }

    public function destroy(Investigation $investigation, ReportWorkflow $workflow)
    {
        Gate::authorize('delete', $investigation);
        abort_if(! $investigation->report, 404);

        $workflow->unassign($investigation, auth()->user());

        return redirect()->route('investigations.index')->with('success', 'Assignment removed.');
    }

    /** BR-08: investigators only ever see their own cases. */
    private function scoped()
    {
        $query = Investigation::query();

        if (! $this->isAdmin()) {
            $query->where('investigations.investigator_id', auth()->id());
        }

        return $query;
    }
}
