<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ReportWorkflow;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    private const PER_PAGE = 15;

    /** Sidebar presets -> report filters. */
    private const VIEWS = ['new', 'under_review', 'in_progress', 'high_risk', 'resolved_closed', 'rejected'];

    public function index(Request $request)
    {
        $this->authorize('viewAny', Report::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(Report::STATUSES)],
            'priority' => ['nullable', Rule::in(Report::PRIORITIES)],
            'view' => ['nullable', Rule::in(self::VIEWS)],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'category' => ['nullable', 'string', 'max:50', Rule::exists('categories', 'slug')],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
        ]);

        $reports = Report::query()
            ->visibleTo($request->user())
            // Never select or eager-load the reporter in a list (BR-08).
            ->select([
                'id', 'tracking_number', 'subject', 'department_id', 'category',
                'priority', 'status', 'anonymous', 'incident_date', 'created_at',
            ])
            ->with('department:id,name')
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($where) use ($search) {
                    $where->where('tracking_number', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['priority'] ?? null, fn ($query, $value) => $query->where('priority', $value))
            ->when($filters['department_id'] ?? null, fn ($query, $value) => $query->where('department_id', $value))
            ->when($filters['category'] ?? null, fn ($query, $value) => $query->where('category', $value))
            ->when($filters['date_from'] ?? null, fn ($query, $value) => $query->whereDate('created_at', '>=', $value))
            ->when($filters['date_to'] ?? null, fn ($query, $value) => $query->whereDate('created_at', '<=', $value))
            ->when($filters['view'] ?? null, fn ($query, $value) => $this->applyView($query, $value))
            ->latest()
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $departments = Department::orderBy('name')->get(['id', 'name']);
        $categories = Category::orderBy('sort_order')->orderBy('name')->get(['slug', 'name']);

        return view('reports.index', compact('reports', 'departments', 'categories', 'filters'));
    }

    public function show(Report $report)
    {
        $this->authorize('view', $report);

        $report->load([
            'department:id,name',
            'evidences',
            'investigation.investigator:id,name',
            'statusHistories.changedBy:id,name',
        ]);

        // Anonymous reports never expose an account (BR: anonymity).
        if (! $report->anonymous) {
            $report->load('user:id,name,email');
        }

        $user = auth()->user();

        $investigators = $user->can('assign', $report)
            ? User::role('Investigator')->orderBy('name')->get(['id', 'name'])
            : [];

        $allowedStatuses = ReportWorkflow::allowedNext($report, $user);
        $canChangePriority = $user->can('changePriority', $report);
        $canUploadEvidence = $user->can('uploadEvidence', $report);

        return view('reports.show', compact(
            'report', 'investigators', 'allowedStatuses', 'canChangePriority', 'canUploadEvidence'
        ));
    }

    public function update(Request $request, Report $report, ReportWorkflow $workflow)
    {
        $this->authorize('update', $report);

        $data = $request->validate([
            'status' => ['nullable', Rule::in(Report::STATUSES)],
            'priority' => ['nullable', Rule::in(Report::PRIORITIES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        // BR-10: priority is admin-only (enforced again by the policy).
        if (! empty($data['priority']) && $data['priority'] !== $report->priority) {
            $this->authorize('changePriority', $report);
            $workflow->changePriority($report, $data['priority'], $request->user());
        }

        // The workflow enforces the BR-06 state machine for every actor.
        if (! empty($data['status']) && $data['status'] !== $report->status) {
            $workflow->changeStatus($report, $data['status'], $request->user(), $data['note'] ?? null);
        }

        return back()->with('success', 'Report updated successfully.');
    }

    public function assign(Request $request, Report $report, ReportWorkflow $workflow)
    {
        // BR-07: admin only, and only while Under Review.
        $this->authorize('assign', $report);

        $data = $request->validate([
            'investigator_id' => ['required', Rule::exists('users', 'id')],
        ]);

        $investigator = User::role('Investigator')->findOrFail($data['investigator_id']);

        // Conflict of interest: nobody investigates a report they submitted.
        if ($report->user_id && $report->user_id === $investigator->id) {
            return back()->withInput()->withErrors([
                'investigator_id' => 'An investigator cannot handle a report they submitted.',
            ]);
        }

        $workflow->assign($report, $investigator, $request->user());

        return redirect()->route('reports.show', $report)->with('success', 'Investigator assigned.');
    }

    public function destroy(Request $request, Report $report)
    {
        $this->authorize('delete', $report);

        $report->delete(); // BR-10: reports are only ever soft-deleted.

        AuditLogger::log('report.deleted', $report, 'Report soft-deleted');

        return redirect()->route('reports.index')->with('success', 'Report deleted.');
    }

    /** Apply a sidebar preset on top of the manual filters. */
    private function applyView($query, string $view): void
    {
        match ($view) {
            'new' => $query->where('status', 'submitted'),
            'under_review' => $query->where('status', 'under_review'),
            'in_progress' => $query->whereIn('status', ['assigned', 'investigating']),
            'high_risk' => $query->whereIn('priority', ['high', 'critical'])
                ->whereNotIn('status', Report::CLOSED_STATUSES),
            'resolved_closed' => $query->whereIn('status', ['resolved', 'closed']),
            'rejected' => $query->where('status', 'rejected'),
            default => null,
        };
    }
}
