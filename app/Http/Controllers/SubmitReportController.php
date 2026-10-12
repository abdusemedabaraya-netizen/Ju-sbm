<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Evidence;
use App\Models\Report;
use App\Services\ReportWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubmitReportController extends Controller
{
   public function create(Request $request)
{
$identified = $request->routeIs('report.create.identified');

    // Identified reporting requires a real account: send guests to login,
    // and Breeze returns them to /report?mode=identified after they log in.
    if ($identified && auth()->guest()) {
        return redirect()->guest(route('login'))
            ->with('login_notice', 'Log in to submit an identified report. No account? Register first.');
    }

    $departments = Department::orderBy('name')->get(['id', 'name']);

    $categoryMap = [
        'bribery'              => 'bribery',
        'fraud'                => 'fraud',
        'corruption'           => 'abuse_of_power',
        'abuse-of-authority'   => 'abuse_of_power',
        'conflict-of-interest' => 'nepotism',
        'misuse-of-resources'  => 'resource_misuse',
        'ethical-misconduct'   => 'harassment',
        'harassment'           => 'harassment',
        'other'                => 'other',
    ];

    $selectedCategory = $categoryMap[$request->query('category')] ?? null;

    return view('public.report-create', compact('departments', 'identified', 'selectedCategory'));
}

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => ['required', Rule::exists('departments', 'id')],
            'category'      => ['required', Rule::in([
                'bribery', 'fraud', 'abuse_of_power', 'nepotism',
                'resource_misuse', 'harassment', 'other',
            ])],
            'subject'       => ['required', 'string', 'max:255'],
            'description'   => ['required', 'string', 'max:10000'],
            'incident_date' => ['nullable', 'date', 'before_or_equal:today'],
            'location'      => ['nullable', 'string', 'max:255'],
            'anonymous'     => ['nullable', 'boolean'],
        ]);

        $uploadedFiles = $this->validatedFiles($request);

        // Guests are always anonymous. Logged-in users choose.
        $anonymous = ! auth()->check() || $request->boolean('anonymous');
        $userId    = $anonymous ? null : auth()->id();

        // Secret access code shown once; only its hash is stored.
        $accessCode = strtoupper(Str::random(10));

        $report = DB::transaction(function () use ($data, $anonymous, $userId, $accessCode, $uploadedFiles) {
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
                'priority'         => 'medium',     // auto-priority mapping comes later
                'status'           => 'submitted',
            ]);

            $report->update([
                'tracking_number' => sprintf('JU-%d-%06d', now()->year, $report->id),   // BR-04
            ]);

            // First status-history row + audit log (no user id, no IP for anonymous)
            app(ReportWorkflow::class)->recordSubmission($report);

            foreach ($uploadedFiles as $file) {
                $path = $file->store(
                    'evidence/' . $report->id,
                    config('jiacrs.evidence_disk')     // 'local' = private, never public
                );

                Evidence::create([
                    'report_id' => $report->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'file_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }

            return $report;
        });

        return redirect()->route('report.submitted')->with([
            'tracking_number' => $report->tracking_number,
            'access_code'     => $accessCode,
        ]);
    }

    public function submitted()
    {
        abort_unless(session()->has('access_code'), 404);

        return view('public.report-submitted', [
            'trackingNumber' => session('tracking_number'),
            'accessCode'     => session('access_code'),
        ]);
    }

    private function validatedFiles(Request $request): array
    {
        if (! $request->hasFile('files')) {
            return [];
        }

        $validated = $request->validate([
            'files'   => ['array', 'max:' . config('jiacrs.max_files')],
            'files.*' => [
                'file',
                'mimes:pdf,doc,docx,jpg,jpeg,png,mp4',
                'max:' . config('jiacrs.max_file_kb'),   // 20480 KB = 20 MB
            ],
        ]);

        return $validated['files'];
    }
}