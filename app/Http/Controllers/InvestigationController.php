<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvestigationFilterRequest;
use App\Http\Requests\StatusNoteRequest;
use App\Http\Requests\StoreCaseCommentRequest;
use App\Http\Requests\StoreInvestigationRequest;
use App\Http\Requests\UpdateInvestigationRequest;
use App\Models\CaseComment;
use App\Models\Category;
use App\Models\Investigation;
use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReportWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class InvestigationController extends Controller
{
    public function index(InvestigationFilterRequest $request)
    {
        Gate::authorize('viewAny', Investigation::class);

        $f       = $request->validated();
        $tab     = $f['tab'] ?? 'all';
        $isAdmin = $this->isAdmin();

        $investigations = Investigation::query()
            ->visibleTo($request->user())
            ->select('investigations.*')
            ->addSelect(['resolved_at' => ReportStatusHistory::select('created_at')
                ->whereColumn('report_id', 'investigations.report_id')
                ->where('to_status', 'resolved')
                ->orderByDesc('id')->limit(1)])
            ->withCasts(['resolved_at' => 'datetime'])
            ->with(['report:id,tracking_number,subject,status,priority,anonymous', 'investigator:id,name'])
            ->whereHas('report', function ($q) use ($tab, $f) {
                $q->when($tab === 'active', fn ($r) => $r->whereIn('status', Report::ACTIVE_STATUSES))
                  ->when($tab === 'completed', fn ($r) => $r->whereIn('status', Report::DONE_STATUSES))
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

        // Tab numbers (same scope as the list).
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

        $reports = Report::unassignedUnderReview()
            ->orderBy('id')
            ->get(['id', 'tracking_number', 'subject', 'priority', 'user_id']);

        $investigators = User::role('Investigator')
            ->select(['users.id', 'users.name'])
            ->withCount(['investigations as open_cases' => fn ($q) => $q->whereHas(
                'report', fn ($r) => $r->whereIn('status', Report::ACTIVE_STATUSES)
            )])
            ->orderBy('name')
            ->get();

        return view('investigations.create', compact('reports', 'investigators'));
    }

    public function store(StoreInvestigationRequest $request, ReportWorkflow $workflow)
    {
        Gate::authorize('create', Investigation::class);

        $investigator = User::role('Investigator')->findOrFail($request->validated('investigator_id'));
        $report       = Report::findOrFail($request->validated('report_id'));

        // Conflict-of-interest, "Under Review" check, status change, history,
        // audit and notification all live in the workflow service.
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

        $user  = auth()->user();
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
    public function update(UpdateInvestigationRequest $request, Investigation $investigation)
    {
        Gate::authorize('update', $investigation);

        $investigation->update($request->validated());

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
    public function resolve(StatusNoteRequest $request, Investigation $investigation, ReportWorkflow $workflow)
    {
        Gate::authorize('resolve', $investigation);
        abort_if(! $investigation->report, 404);

        // BR-09 (findings + recommendations required before "resolved") is
        // enforced centrally by the workflow service.
        $workflow->changeStatus($investigation->report, 'resolved', $request->user(), $request->validated('note'));

        return redirect()->route('investigations.show', $investigation)
            ->with('success', 'Case marked as resolved. An admin will review it and close the case.');
    }

    /** Admin approves the resolution. */
    public function close(StatusNoteRequest $request, Investigation $investigation, ReportWorkflow $workflow)
    {
        Gate::authorize('close', $investigation);
        abort_if(! $investigation->report, 404);

        $workflow->changeStatus($investigation->report, 'closed', $request->user(), $request->validated('note'));

        return back()->with('success', 'Case approved and closed.');
    }

    /** Internal case notes: never shown to the reporter. */
    public function comment(StoreCaseCommentRequest $request, Investigation $investigation)
    {
        Gate::authorize('comment', $investigation);
        abort_if(! $investigation->report, 404);

        CaseComment::create([
            'report_id'   => $investigation->report_id,
            'user_id'     => $request->user()->id,
            'comment'     => $request->validated('comment'),
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
}
