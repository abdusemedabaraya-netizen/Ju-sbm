<?php

namespace App\Services;

use App\Models\Evidence;
use App\Models\Report;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class EvidenceStorage
{
    private const EXT = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'application/pdf' => 'pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'video/mp4'       => 'mp4',
    ];

    public function __construct(private readonly ImageProcessor $images)
    {
    }

    /**
     * Store every upload in one call. The sequence continues from the highest
     * existing evidence id so anonymous files keep neutral, non-colliding names.
     *
     * @param array<int, UploadedFile> $files
     */
    public function storeMany(Report $report, array $files, ?int $uploadedBy): void
    {
        $sequence = ($report->evidences()->max('id') ?? 0) + 1;

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $this->store($report, $file, $uploadedBy, $sequence++);
            }
        }
    }

    public function store(Report $report, UploadedFile $file, ?int $uploadedBy, int $sequence): Evidence
    {
        $mime = $file->getMimeType();   // detected from file CONTENT, not the file name
        $ext  = self::EXT[$mime] ?? null;

        if (! $ext) {
            throw ValidationException::withMessages(['files' => 'Unsupported file type.']);
        }

        $disk     = Storage::disk(config('jiacrs.evidence_disk'));
        $dir      = "evidence/{$report->id}";
        $stored   = Str::random(40) . '.' . $ext;     // random name: nothing guessable
        $path     = "{$dir}/{$stored}";
        $stripped = false;

        if (in_array($mime, ['image/jpeg', 'image/png'], true)) {
            // Re-encoding the pixels drops EXIF (GPS, camera, date) and other metadata.
            $contents = $this->images->stripMetadata($file->getRealPath());
            $disk->put($path, $contents);
            $hash     = hash('sha256', $contents);
            $size     = strlen($contents);
            $stripped = true;
        } else {
            $disk->putFileAs($dir, $file, $stored);
            $hash = hash_file('sha256', $file->getRealPath());
            $size = $file->getSize();
        }

        // File names can identify people, so anonymous reports get a neutral name.
        $original = $report->anonymous
            ? "evidence-{$sequence}.{$ext}"
            : $this->cleanName($file->getClientOriginalName(), $ext);

        return Evidence::create([
            'report_id'         => $report->id,
            'uploaded_by'       => $uploadedBy,
            'original_name'     => $original,
            'stored_path'       => $path,
            'mime_type'         => $mime,
            'size'              => $size,
            'sha256'            => $hash,
            'metadata_stripped' => $stripped,
        ]);
    }

    private function cleanName(string $name, string $ext): string
    {
        $base = pathinfo($name, PATHINFO_FILENAME);
        $base = preg_replace('/[^\p{L}\p{N}_\- ]+/u', '', $base);
        $base = Str::limit(trim($base), 80, '');

        return ($base !== '' ? $base : 'evidence') . '.' . $ext;
    }
}
