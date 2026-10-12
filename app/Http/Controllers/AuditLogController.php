<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'action' => ['nullable', 'string', 'max:100'],
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date'],
        ]);

        $logs = AuditLog::with('user:id,name')
            ->when($request->action, fn ($q, $v) => $q->where('action', $v))
            ->when($request->from, fn ($q, $v) => $q->where('created_at', '>=', Carbon::parse($v)->startOfDay()))
            ->when($request->to, fn ($q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $actions = AuditLog::select('action')->distinct()->orderBy('action')->pluck('action');

        return view('audit-logs.index', compact('logs', 'actions'));
    }
}
