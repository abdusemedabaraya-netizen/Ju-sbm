<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // BR-08 role scoping lives in one place: Report::scopeVisibleTo.
        // Investigators only see their own assigned cases.
        $base = fn () => Report::query()->visibleTo($user);

        $stats = [
            'total'     => $base()->count(),
            'open'      => $base()->whereNotIn('status', Report::CLOSED_STATUSES)->count(),
            'resolved'  => $base()->where('status', 'resolved')->count(),
            'high_risk' => $base()->whereIn('priority', Report::HIGH_RISK_PRIORITIES)
                                  ->whereNotIn('status', Report::CLOSED_STATUSES)->count(),
        ];

        // Last 6 months (zero-filled)
        $from = Carbon::now()->startOfMonth()->subMonths(5);
        $ymExpr = DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', created_at) as ym"
            : "DATE_FORMAT(created_at, '%Y-%m') as ym";
        $counts = $base()->where('created_at', '>=', $from)
            ->selectRaw("$ymExpr, COUNT(*) as total")
            ->groupBy('ym')->pluck('total', 'ym');

        $labels = $data = [];
        for ($i = 0; $i < 6; $i++) {
            $m = $from->copy()->addMonths($i);
            $labels[] = $m->format('M Y');
            $data[]   = (int) ($counts[$m->format('Y-m')] ?? 0);
        }
        $trend = ['labels' => $labels, 'data' => $data];

        // By category
        $cat = $base()->selectRaw('category, COUNT(*) as total')
            ->groupBy('category')->orderByDesc('total')->get();
        $byCategory = [
            'labels' => $cat->pluck('category')->all(),
            'data'   => $cat->pluck('total')->map(fn ($v) => (int) $v)->all(),
        ];

        // Latest reports (never select reporter identity here)
        $latest = $base()
            ->select('id', 'tracking_number', 'subject', 'category', 'priority', 'status', 'created_at')
            ->latest('created_at')->limit(8)->get();

        return view('admin.dashboard', compact('stats', 'trend', 'byCategory', 'latest'));
    }
}