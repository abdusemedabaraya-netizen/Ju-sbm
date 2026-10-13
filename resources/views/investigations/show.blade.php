@extends('layouts.admin')

@section('title', 'Case ' . $investigation->report?->tracking_number)
@section('page_title', 'Case Workspace')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('investigations.index') }}">Investigations</a></li>
  <li class="breadcrumb-item active" aria-current="page">{{ $investigation->report?->tracking_number }}</li>
@endsection

@php
  $daysOpen = $investigation->created_at?->diffInDays(now());
  $status = $report?->status;
  $isTerminal = in_array($status, ['resolved', 'closed'], true);
@endphp

@section('content')
  @if ($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
      </ul>
    </div>
  @endif

  @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif

  <div class="row g-3">
    {{-- ============ Left column ============ --}}
    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">{{ $report?->subject }}</h3>
          <span><code>{{ $report?->tracking_number }}</code></span>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9">@if ($report)<x-status-badge :status="$report->status" />@endif</dd>

            <dt class="col-sm-3">Priority</dt>
            <dd class="col-sm-9">@if ($report)<x-priority-badge :priority="$report->priority" />@endif</dd>

            <dt class="col-sm-3">Department</dt>
            <dd class="col-sm-9">{{ $report?->department?->name ?? '—' }}</dd>

            <dt class="col-sm-3">Investigator</dt>
            <dd class="col-sm-9">{{ $investigation->investigator?->name ?? '—' }}</dd>

            <dt class="col-sm-3">Assigned</dt>
            <dd class="col-sm-9">
              {{ optional($investigation->created_at)->format('d M Y H:i') }}
              @if ($daysOpen !== null)
                <span class="badge text-bg-{{ (! $isTerminal && $daysOpen > 30) ? 'danger' : 'light' }} ms-1">
                  {{ $daysOpen }} day(s) open
                </span>
              @endif
            </dd>

            <dt class="col-sm-3">Description</dt>
            <dd class="col-sm-9" style="white-space: pre-line;">{{ $report?->description ?: '—' }}</dd>
          </dl>
          @if ($report)
            <a href="{{ route('reports.show', $report) }}" class="btn btn-sm btn-outline-primary mt-3">
              <i class="bi bi-box-arrow-up-right"></i> Open full report
            </a>
          @endif
        </div>
      </div>

      {{-- Findings & recommendations (BR-09: only the assigned investigator edits). --}}
      <div class="card mb-3">
        <div class="card-header"><h3 class="card-title mb-0">Findings &amp; recommendations</h3></div>
        <div class="card-body">
          @if ($flags['canEditFindings'])
            <form method="POST" action="{{ route('investigations.update', $investigation) }}">
              @csrf
              @method('PUT')
              <div class="mb-3">
                <label class="form-label" for="findings">Findings</label>
                <textarea id="findings" name="findings" rows="8"
                          class="form-control @error('findings') is-invalid @enderror">{{ old('findings', $investigation->findings) }}</textarea>
                @error('findings')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <div class="mb-3">
                <label class="form-label" for="recommendations">Recommendations</label>
                <textarea id="recommendations" name="recommendations" rows="5"
                          class="form-control @error('recommendations') is-invalid @enderror">{{ old('recommendations', $investigation->recommendations) }}</textarea>
                @error('recommendations')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <button class="btn btn-primary"><i class="bi bi-save"></i> Save findings</button>
            </form>
          @else
            <h6>Findings</h6>
            <div class="mb-3" style="white-space: pre-line;">
              {!! $investigation->findings ? e($investigation->findings) : '<span class="text-secondary">Nothing recorded yet.</span>' !!}
            </div>
            <h6>Recommendations</h6>
            <div class="mb-3" style="white-space: pre-line;">
              {!! $investigation->recommendations ? e($investigation->recommendations) : '<span class="text-secondary">Nothing recorded yet.</span>' !!}
            </div>
            <p class="text-secondary small mb-0">Only the assigned investigator can edit findings and recommendations (BR-09).</p>
          @endif
        </div>
      </div>

      {{-- Internal case notes (staff only; never shown to the reporter). --}}
      <div class="card mb-3">
        <div class="card-header">
          <h3 class="card-title mb-0">Internal case notes</h3>
        </div>
        <div class="card-body">
          @forelse ($report?->comments ?? [] as $comment)
            <div class="d-flex {{ $loop->last ? '' : 'mb-3' }}">
              <div class="me-3"><i class="bi bi-chat-left-text text-primary"></i></div>
              <div class="flex-grow-1 border-bottom pb-2">
                <div class="d-flex justify-content-between">
                  <strong>{{ $comment->user?->name ?? 'System' }}</strong>
                  <small class="text-secondary">{{ optional($comment->created_at)->format('d M Y H:i') }}</small>
                </div>
                <div style="white-space: pre-line;">{{ $comment->comment }}</div>
              </div>
            </div>
          @empty
            <p class="text-secondary mb-0">No internal notes recorded.</p>
          @endforelse
        </div>
        @if ($flags['canComment'])
          <div class="card-footer">
            <form method="POST" action="{{ route('investigations.comments.store', $investigation) }}">
              @csrf
              <div class="mb-2">
                <textarea name="comment" rows="2" class="form-control @error('comment') is-invalid @enderror"
                          maxlength="5000" placeholder="Add an internal note (staff only)" required>{{ old('comment') }}</textarea>
                @error('comment')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>
              <button class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i> Add note</button>
            </form>
          </div>
        @endif
      </div>
    </div>

    {{-- ============ Right column ============ --}}
    <div class="col-lg-4">
      {{-- Case transitions. --}}
      <div class="card mb-3">
        <div class="card-header"><h3 class="card-title mb-0">Case actions</h3></div>
        <div class="card-body">
          @if ($flags['canStart'])
            <form method="POST" action="{{ route('investigations.start', $investigation) }}" class="mb-3">
              @csrf
              <button class="btn btn-warning w-100"><i class="bi bi-play-fill"></i> Start investigation</button>
              <div class="form-text">Moves the report from Assigned to Investigating.</div>
            </form>
          @endif

          @if ($flags['canResolve'])
            <form method="POST" action="{{ route('investigations.resolve', $investigation) }}" class="mb-3">
              @csrf
              <div class="mb-2">
                <label class="form-label" for="resolve-note">Resolution note</label>
                <textarea name="note" id="resolve-note" rows="2" class="form-control" maxlength="1000"
                          placeholder="Summary for the report history">{{ old('note') }}</textarea>
              </div>
              <button class="btn btn-success w-100"><i class="bi bi-check2-circle"></i> Mark as resolved</button>
              <div class="form-text">Findings and recommendations are required first.</div>
            </form>
          @endif

          @if ($flags['canClose'])
            <form method="POST" action="{{ route('investigations.close', $investigation) }}">
              @csrf
              <div class="mb-2">
                <label class="form-label" for="close-note">Closing note</label>
                <textarea name="note" id="close-note" rows="2" class="form-control" maxlength="1000"
                          placeholder="Summary for the report history">{{ old('note') }}</textarea>
              </div>
              <button class="btn btn-dark w-100"><i class="bi bi-archive"></i> Close case</button>
            </form>
          @endif

          @unless ($flags['canStart'] || $flags['canResolve'] || $flags['canClose'])
            <p class="text-secondary small mb-0">
              @if ($isTerminal)
                This case is {{ $status }}. No further transitions are available.
              @else
                No case transitions are available for your role at this stage.
              @endif
            </p>
          @endunless
        </div>
      </div>

      {{-- Evidence for the case. --}}
      <div class="card mb-3">
        <div class="card-header"><h3 class="card-title mb-0">Evidence</h3></div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @forelse ($report?->evidences ?? [] as $evidence)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                  <i class="bi bi-paperclip"></i> {{ $evidence->original_name }}
                  @if ($evidence->metadata_stripped)
                    <i class="bi bi-shield-check text-success" title="Metadata stripped"></i>
                  @endif
                </span>
                <a href="{{ route('evidence.download', $evidence) }}" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-download"></i>
                </a>
              </li>
            @empty
              <li class="list-group-item text-center text-secondary py-4">No evidence attached.</li>
            @endforelse
          </ul>
        </div>
        @if ($flags['canUpload'] && $report)
          <div class="card-footer">
            <form action="{{ route('evidence.store', $report) }}" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="mb-2">
                <input type="file" name="files[]" class="form-control form-control-sm" multiple
                       accept=".pdf,.docx,.jpg,.jpeg,.png,.mp4">
                <div class="form-text">PDF, DOCX, JPG, PNG or MP4.</div>
              </div>
              <button class="btn btn-sm btn-primary w-100"><i class="bi bi-upload"></i> Upload evidence</button>
            </form>
          </div>
        @endif
      </div>

      {{-- Status history. --}}
      <div class="card mb-3">
        <div class="card-header"><h3 class="card-title mb-0">Case timeline</h3></div>
        <div class="card-body">
          @forelse ($report?->statusHistories ?? [] as $history)
            <div class="d-flex {{ $loop->last ? '' : 'mb-3' }}">
              <div class="me-3"><i class="bi bi-clock-history text-primary"></i></div>
              <div class="flex-grow-1 border-bottom pb-2">
                <span>
                  @if ($history->from_status)
                    <x-status-badge :status="$history->from_status" />
                    <i class="bi bi-arrow-right mx-1"></i>
                  @endif
                  <x-status-badge :status="$history->to_status" />
                </span>
                <div class="small text-secondary mt-1">
                  {{ optional($history->created_at)->format('d M Y H:i') }}
                  · by {{ $history->changedBy?->name ?? 'System' }}
                  @if ($history->note) — {{ $history->note }} @endif
                </div>
              </div>
            </div>
          @empty
            <p class="text-secondary mb-0">No status history recorded.</p>
          @endforelse
        </div>
      </div>

      @if ($flags['canRemove'])
        <div class="card border-danger">
          <div class="card-header text-bg-danger"><h3 class="card-title mb-0">Danger zone</h3></div>
          <div class="card-body">
            <p class="small text-secondary">Removing the assignment returns the report to Under Review.</p>
            <form method="POST" action="{{ route('investigations.destroy', $investigation) }}"
                  onsubmit="return confirm('Remove this assignment? The report returns to Under Review.');">
              @csrf
              @method('DELETE')
              <button class="btn btn-outline-danger w-100"><i class="bi bi-person-dash"></i> Remove assignment</button>
            </form>
          </div>
        </div>
      @endif
    </div>
  </div>
@endsection
