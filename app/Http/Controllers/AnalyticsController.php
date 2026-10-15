<?php

namespace App\Http\Controllers;

use App\Http\Requests\AnalyticsFilterRequest;
use App\Models\Category;
use App\Models\Department;
use App\Models\Report;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(AnalyticsFilterRequest $request)
    {
        $data = $request->validated();

        $from   = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->subMonths(11)->startOfMonth();
        $to     = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : now()->endOfDay();
        $deptId = $data['department_id'] ?? null;

        // Only report data is read here: reporter identity is never queried.
        $base = fn () => DB::table('reports')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$from, $to])
            ->when($deptId, fn ($q) => $q->where('department_id', $deptId));

        $total    = $base()->count();
        $open     = $base()->whereNotIn('status', Report::CLOSED_STATUSES)->count();
        $done     = $base()->whereIn('status', Report::DONE_STATUSES)->count();
        $rejected = $base()->where('status', 'rejected')->count();
        $highOpen = $base()->whereIn('priority', Report::HIGH_RISK_PRIORITIES)->whereNotIn('status', Report::CLOSED_STATUSES)->count();

        $hours = DB::table('report_status_histories as h')
            ->join('reports as r', 'r.id', '=', 'h.report_id')
            ->whereNull('r.deleted_at')
            ->where('h.to_status', 'resolved')
            ->whereBetween('r.created_at', [$from, $to])
            ->when($deptId, fn ($q) => $q->where('r.department_id', $deptId))
            ->avg(DB::raw('TIMESTAMPDIFF(HOUR, r.created_at, h.created_at)'));

        $stats = [
            'total'           => $total,
            'open'            => $open,
            'resolved'        => $done,
            'rejected'        => $rejected,
            'high_open'       => $highOpen,
            'resolution_rate' => $total ? round($done / $total * 100) : 0,
            'avg_days'        => $hours !== null ? round($hours / 24, 1) : null,
        ];

        /* ---- Monthly trend (all reports and high-risk reports) ---- */
        $monthly = $base()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as total, SUM(CASE WHEN priority IN ('high','critical') THEN 1 ELSE 0 END) as high")
            ->groupBy('ym')->get()->keyBy('ym');

        $labels = $all = $high = [];
        $cursor = $from->copy()->startOfMonth();
        for ($i = 0; $cursor->lte($to) && $i < 36; $i++, $cursor->addMonth()) {
            $row      = $monthly->get($cursor->format('Y-m'));
            $labels[] = $cursor->format('M Y');
            $all[]    = (int) ($row->total ?? 0);
            $high[]   = (int) ($row->high ?? 0);
        }

        /* ---- Status, priority, category ---- */
        $byStatus   = $base()->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $byPriority = $base()->selectRaw('priority, COUNT(*) as c')->groupBy('priority')->pluck('c', 'priority');
        $byCategory = $base()->selectRaw('category, COUNT(*) as c')->groupBy('category')->orderByDesc('c')->pluck('c', 'category');
        $names      = Category::pluck('name', 'slug');

        $charts = [
            'trend'    => ['labels' => $labels, 'all' => $all, 'high' => $high],
            'status'   => [
                'labels' => array_map([Report::class, 'label'], Report::STATUSES),
                'data'   => array_map(fn ($s) => (int) ($byStatus[$s] ?? 0), Report::STATUSES),
            ],
            'priority' => [
                'labels' => array_map([Report::class, 'label'], Report::PRIORITIES),
                'data'   => array_map(fn ($p) => (int) ($byPriority[$p] ?? 0), Report::PRIORITIES),
            ],
            'category' => [
                'labels' => $byCategory->keys()->map(fn ($s) => $names[$s] ?? Report::label($s))->all(),
                'data'   => $byCategory->values()->map(fn ($v) => (int) $v)->all(),
            ],
        ];

        /* ---- Department table ---- */
        $departments = DB::table('reports')
            ->join('departments', 'departments.id', '=', 'reports.department_id')
            ->whereNull('reports.deleted_at')
            ->whereBetween('reports.created_at', [$from, $to])
            ->when($deptId, fn ($q) => $q->where('reports.department_id', $deptId))
            ->groupBy('departments.id', 'departments.name')
            ->selectRaw("departments.name,
                COUNT(*) as total,
                SUM(CASE WHEN reports.status IN ('resolved','closed') THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN reports.priority IN ('high','critical') THEN 1 ELSE 0 END) as high_risk")
            ->orderByDesc('total')->limit(15)->get();

        $departmentList = Department::orderBy('name')->get(['id', 'name']);

        return view('analytics.index', [
            'stats'          => $stats,
            'charts'         => $charts,
            'departments'    => $departments,
            'departmentList' => $departmentList,
            'filters'        => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'department_id' => $deptId],
        ]);
    }
}
