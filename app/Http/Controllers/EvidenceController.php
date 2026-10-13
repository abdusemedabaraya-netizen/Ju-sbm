<?php

namespace App\Http\Controllers;

use App\Models\Evidence;
use App\Models\Report;
use App\Services\EvidenceStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Report $report, EvidenceStorage $storage)
    {
        abort_unless(auth()->check(), 403);
        $user = $request->user();
        abort_unless($user->hasAnyRole(['Super Admin', 'Admin', 'Investigator']), 403);

        $data = $request->validate([
            'files' => ['required', 'array', 'max:'.config('jiacrs.max_files')],
            'files.*' => ['file', 'mimes:pdf,docx,jpg,jpeg,png,mp4', 'max:'.config('jiacrs.max_file_kb')],
        ]);

        $sequence = ($report->evidences()->max('id') ?? 0) + 1;
        foreach ($request->file('files') as $file) {
            if ($file) {
                $storage->store($report, $file, $user->id, $sequence++);
            }
        }

        return back()->with('success', 'Evidence uploaded.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Evidence $evidence)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Evidence $evidence)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Evidence $evidence)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Evidence $evidence)
    {
        //
    }

    public function download(Evidence $evidence)
    {
        $user = auth()->user();
        abort_unless($user, 403);

        // Admins/Super Admins may download any evidence; an investigator may
        // only download evidence for a report assigned to them.
        $isAdmin = $user->hasAnyRole(['Super Admin', 'Admin']);
        $isAssignedInvestigator = $user->hasRole('Investigator')
            && $evidence->report?->investigation?->investigator_id === $user->id;

        abort_unless($isAdmin || $isAssignedInvestigator, 403);

        $disk = Storage::disk(config('jiacrs.evidence_disk'));
        if (! $disk->exists($evidence->stored_path)) {
            abort(404);
        }

        return $disk->download($evidence->stored_path, $evidence->original_name);
    }
}
