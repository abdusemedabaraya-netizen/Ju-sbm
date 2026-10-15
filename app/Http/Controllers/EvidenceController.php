<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEvidenceRequest;
use App\Models\Evidence;
use App\Models\Report;
use App\Services\EvidenceStorage;
use Illuminate\Support\Facades\Storage;

/** Evidence is only ever uploaded to a report and downloaded; it is never edited. */
class EvidenceController extends Controller
{
    public function store(StoreEvidenceRequest $request, Report $report, EvidenceStorage $storage)
    {
        $storage->storeMany($report, $request->file('files') ?? [], $request->user()->id);

        return back()->with('success', 'Evidence uploaded.');
    }

    public function download(Evidence $evidence)
    {
        $user = auth()->user();
        abort_unless($user, 403);

        // Admins/Super Admins may download any evidence; an investigator may
        // only download evidence for a report assigned to them.
        $isAdmin = $user->isAdmin();
        $isAssignedInvestigator = $user->isInvestigator()
            && $evidence->report?->investigation?->investigator_id === $user->id;

        abort_unless($isAdmin || $isAssignedInvestigator, 403);

        $disk = Storage::disk(config('jiacrs.evidence_disk'));
        if (! $disk->exists($evidence->stored_path)) {
            abort(404);
        }

        return $disk->download($evidence->stored_path, $evidence->original_name);
    }
}
