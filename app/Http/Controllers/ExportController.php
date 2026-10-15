<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportFilterRequest;
use App\Models\Department;
use App\Models\Report;
use App\Services\AuditLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExportController extends Controller
{
    public function index()
    {
        $departments = Department::orderBy('name')->get(['id', 'name']);

        return view('exports.index', compact('departments'));
    }

    public function csv(ExportFilterRequest $request)
    {
        [$rows, $meta] = $this->fetch($request->validated());

        AuditLogger::log('report.exported', null, "CSV: {$meta['label']} ({$rows->count()} rows)");

        $filename = 'jiacrs-' . $meta['period'] . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows Amharic / Afaan Oromo correctly

            fputcsv($out, ['Tracking number', 'Subject', 'Category', 'Department', 'Priority', 'Status', 'Anonymous', 'Submitted', 'Last updated']);

            foreach ($rows as $r) {
                fputcsv($out, array_map([$this, 'safe'], [
                    $r->tracking_number, $r->subject, $r->category, $r->department,
                    $r->priority, $r->status, $r->anonymous ? 'Yes' : 'No', $r->created_at, $r->updated_at,
                ]));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Printable page: use the browser's "Save as PDF". */
    public function print(ExportFilterRequest $request)
    {
        [$rows, $meta] = $this->fetch($request->validated());

        AuditLogger::log('report.exported', null, "Print: {$meta['label']} ({$rows->count()} rows)");

        $summary = [
            'total'    => $rows->count(),
            'status'   => $rows->countBy('status'),
            'priority' => $rows->countBy('priority'),
        ];

        return view('exports.print', compact('rows', 'meta', 'summary'));
    }

    private function fetch(array $data): array
    {
        [$from, $to, $title] = match ($data['period']) {
            'daily'   => [now()->startOfDay(), now()->endOfDay(), 'Daily report'],
            'weekly'  => [now()->startOfWeek(), now()->endOfWeek(), 'Weekly report'],
            'monthly' => [now()->startOfMonth(), now()->endOfMonth(), 'Monthly report'],
            'annual'  => [now()->startOfYear(), now()->endOfYear(), 'Annual integrity report'],
            'custom'  => [Carbon::parse($data['from'])->startOfDay(), Carbon::parse($data['to'])->endOfDay(), 'Custom report'],
        };

        if ($to->lt($from)) {
            throw ValidationException::withMessages(['to' => 'The end date must be on or after the start date.']);
        }

        $label = $title . ' (' . $from->format('d M Y') . ' - ' . $to->format('d M Y') . ')';

        // The reporter (user_id) is never selected: exports cannot reveal who reported.
        $rows = DB::table('reports as r')
            ->leftJoin('departments as d', 'd.id', '=', 'r.department_id')
            ->leftJoin('categories as c', 'c.slug', '=', 'r.category')
            ->whereNull('r.deleted_at')
            ->whereBetween('r.created_at', [$from, $to])
            ->when(($data['status'] ?? null) === 'open', fn ($q) => $q->whereNotIn('r.status', Report::CLOSED_STATUSES))
            ->when(($data['status'] ?? null) && $data['status'] !== 'open', fn ($q) => $q->where('r.status', $data['status']))
            ->when($data['priority'] ?? null, fn ($q, $v) => $q->where('r.priority', $v))
            ->when($data['department_id'] ?? null, fn ($q, $v) => $q->where('r.department_id', $v))
            ->orderBy('r.created_at')
            ->limit(10000)
            ->select([
                'r.tracking_number', 'r.subject',
                DB::raw('COALESCE(c.name, r.category) as category'),
                'd.name as department', 'r.priority', 'r.status', 'r.anonymous', 'r.created_at', 'r.updated_at',
            ])
            ->get();

        return [$rows, [
            'label'  => $label,
            'period' => $data['period'],
            'from'   => $from,
            'to'     => $to,
            'title'  => $title,
        ]];
    }

    /** Stops spreadsheet formula injection (=, +, -, @ at the start of a cell). */
    private function safe($value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}
