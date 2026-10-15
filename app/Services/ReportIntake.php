<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Report;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The single entry point for turning a validated submission into a stored
 * report: it owns code generation, tracking-number generation, automatic
 * priority, evidence storage and the workflow bookkeeping around submission.
 *
 * The controller is left with only HTTP concerns (auth context + redirect).
 */
class ReportIntake
{
    public function __construct(
        private readonly ReportWorkflow $workflow,
        private readonly EvidenceStorage $evidence,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data   validated StoreReportRequest payload
     * @param  array<int, UploadedFile>  $files
     * @param  int|null  $reporterId  null means anonymous (guest or opted in)
     * @return array{report: Report, access_code: string}
     */
    public function submit(array $data, array $files, ?int $reporterId): array
    {
        $anonymous  = $reporterId === null;
        $accessCode = strtoupper(Str::random(10));
        $priority   = Category::priorityFor($data['category']) ?? 'medium';

        $report = DB::transaction(function () use ($data, $reporterId, $anonymous, $accessCode, $priority) {
            $report = Report::create([
                'tracking_number'  => 'TMP-' . Str::uuid(),
                'access_code_hash' => Hash::make($accessCode),
                'user_id'          => $reporterId,
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

            // The public tracking number embeds the auto-increment id, so it can
            // only be built once the row exists.
            $report->update([
                'tracking_number' => sprintf('JU-%d-%06d', now()->year, $report->id),
            ]);

            return $report;
        });

        $this->evidence->storeMany($report, $files, $reporterId);

        // Record submission and notify admins / the registered reporter.
        $this->workflow->recordSubmission($report);
        $this->workflow->notifyNewReport($report);

        return ['report' => $report, 'access_code' => $accessCode];
    }
}
