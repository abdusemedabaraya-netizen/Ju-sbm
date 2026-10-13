<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Report;
use App\Services\EvidenceStorage;
use App\Services\ReportWorkflow;
use App\Services\SiteSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SubmitReportController extends Controller
{
    public function create()
    {
<<<<<<< HEAD
        $departments = Department::orderBy('name')->get(['id', 'name']);
        $categories = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['slug', 'name']);

=======
        SiteSettings::applyToConfig();
        if (! config('jiacrs.public_reporting_enabled')) {
            return view('public.report-closed');
        }

        $departments = Department::orderBy('name')->get(['id', 'name']);
        $categories  = Category::active()->orderBy('sort_order')->get(['id', 'slug', 'name']);

>>>>>>> 4ee7626 (updated)
        return view('public.report-create', compact('departments', 'categories'));
    }

    public function store(Request $request, ReportWorkflow $workflow)
    {
        SiteSettings::applyToConfig();
        if (! config('jiacrs.public_reporting_enabled')) {
            throw ValidationException::withMessages(['subject' => 'Public reporting is temporarily closed.']);
        }

        $categorySlugs = Category::active()->pluck('slug')->toArray();
        $data = $request->validate([
            'department_id' => ['required', Rule::exists('departments', 'id')],
            'category'      => ['required', 'string', Rule::in($categorySlugs)],
            'subject'       => ['required', 'string', 'max:255'],
            'description'   => ['required', 'string', 'max:10000'],
            'incident_date' => ['nullable', 'date', 'before_or_equal:today'],
            'location'      => ['nullable', 'string', 'max:255'],
            'anonymous'     => ['nullable', 'boolean'],
            'files'         => ['nullable', 'array', 'max:' . config('jiacrs.max_files')],
            'files.*'       => ['file', 'mimes:pdf,docx,jpg,jpeg,png,mp4', 'max:' . config('jiacrs.max_file_kb')],
        ]);

        // Guests are always anonymous. Logged-in users choose.
        $anonymous = ! auth()->check() || $request->boolean('anonymous');
        $userId    = $anonymous ? null : auth()->id();

        // Secret code shown once; only its hash is stored.
        $accessCode = strtoupper(Str::random(10));
        $priority   = Category::priorityFor($data['category']) ?? 'medium';

        $report = DB::transaction(function () use ($data, $anonymous, $userId, $accessCode, $priority) {
            $report = Report::create([
                'tracking_number'  => 'TMP-' . Str::uuid(),
                'access_code_hash' => Hash::make($accessCode),
                'user_id'          => $userId,
                'department_id'    => $data['department_id'],
                'category'         => $data['category'],
                'subject'          => $data['subject'],
                'description'      => $data['description'],
                'incident_date'    => $data['incident_date'] ?? null,
                'location'         => $data['location'] ?? null,
                'anonymous'        => $anonymous,
                'priority'         => $priority,
                'status'           => 'submitted',
            ]);

            $report->update([
                'tracking_number' => sprintf('JU-%d-%06d', now()->year, $report->id),
            ]);

            return $report;
        });

        // Store evidence if any
        if ($request->hasFile('files')) {
            $storage  = new EvidenceStorage();
            $sequence = 1;
            foreach ($request->file('files') as $file) {
                if ($file) {
                    $storage->store($report, $file, $userId, $sequence++);
                }
            }
        }

        // Record submission and notify
        $workflow->recordSubmission($report);
        $workflow->notifyNewReport($report);

        // No IP address or user agent is stored for any report.
        return redirect()->route('report.submitted')->with([
            'tracking_number' => $report->tracking_number,
            'access_code'     => $accessCode,
        ]);
    }

    public function submitted()
    {
        // The code is only available right after submission.
        abort_unless(session()->has('access_code'), 404);

        return view('public.report-submitted', [
            'trackingNumber' => session('tracking_number'),
            'accessCode'     => session('access_code'),
        ]);
    }
}