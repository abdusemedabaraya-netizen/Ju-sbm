@props(['evidences', 'report' => null, 'canUpload' => false])

{{--
    Shared evidence card used by the Report page and the Case workspace.
    Renders the attachment list and, when allowed, the staff upload form.
--}}
<div class="card mb-3">
  <div class="card-header"><h3 class="card-title mb-0">Evidence</h3></div>
  <div class="card-body p-0">
    <ul class="list-group list-group-flush">
      @forelse ($evidences as $evidence)
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <span>
            <i class="bi bi-paperclip"></i> {{ $evidence->original_name }}
            <small class="text-secondary">
              ({{ number_format(($evidence->size ?? 0) / 1024, 1) }} KB)
              @if ($evidence->metadata_stripped)
                · <i class="bi bi-shield-check" title="Metadata stripped"></i> metadata removed
              @endif
            </small>
          </span>
          <a href="{{ route('evidence.download', $evidence) }}" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-download"></i> Download
          </a>
        </li>
      @empty
        <li class="list-group-item text-center text-secondary py-4">No evidence attached.</li>
      @endforelse
    </ul>
  </div>

  @if ($canUpload && $report)
    <div class="card-footer">
      <form action="{{ route('evidence.store', $report) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-2">
          <input type="file" name="files[]" class="form-control form-control-sm" multiple
                 accept=".pdf,.docx,.jpg,.jpeg,.png,.mp4">
          <div class="form-text">
            PDF, DOCX, JPG, PNG or MP4 · up to {{ config('jiacrs.max_files') }} files.
          </div>
        </div>
        <button class="btn btn-sm btn-primary w-100"><i class="bi bi-upload"></i> Upload evidence</button>
      </form>
    </div>
  @endif
</div>
