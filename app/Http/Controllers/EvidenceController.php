<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    public function store(Request $request, Report $report)
    {
        $this->authorizeReport($report);

        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,jpg,jpeg,png,gif,zip',
                'max:10240', // 10 MB
            ],
        ]);

        $file = $data['file'];

        Evidence::create([
            'report_id' => $report->id,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $file->store('evidence', 'public'),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return back()->with('success', 'Evidence attached to the case.');
    }

    public function download(Evidence $evidence)
    {
        $this->authorizeReport($evidence->report);

        return Storage::disk('public')->download($evidence->file_path, $evidence->file_name);
    }

    private function authorizeReport(Report $report): void
    {
        $user = auth()->user();

        $allowed = $user->hasAnyRole(['Admin', 'Super Admin'])
            || ($user->hasRole('Investigator') && $report->investigation?->investigator_id === $user->id);

        abort_unless($allowed, 403);
    }
}