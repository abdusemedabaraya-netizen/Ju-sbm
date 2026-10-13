@extends('layouts.admin')

@section('title', 'Report Details')
@section('page_title', 'Report Details')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('reports.index') }}">Reports</a></li>
  <li class="breadcrumb-item active" aria-current="page">{{ $report->tracking_number }}</li>
@endsection

@section('content')
  <div class="row g-3">
    {{-- ============ Left column ============ --}}
    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">{{ $report->subject }}</h3>
          <span class="text-secondary"><code>{{ $report->tracking_number }}</code></span>
        </div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-3">Status</dt>
            <dd class="col-sm-9"><x-status-badge :status="$report->status" /></dd>

            <dt class="col-sm-3">Priority</dt>
            <dd class="col-sm-9"><x-priority-badge :priority="$report->priority" /></dd>

            <dt class="col-sm-3">Category</dt>
            <dd class="col-sm-9">{{ ucwords(str_replace('_', ' ', $report->category)) }}</dd>

            <dt class="col-sm-3">Department</dt>
            <dd class="col-sm-9">{{ $report->department?->name ?? '—' }}</dd>

            <dt class="col-sm-3">Location</dt>
            <dd class="col-sm-9">{{ $report->location ?: '—' }}</dd>

            <dt class="col-sm-3">Incident date</dt>
            <dd class="col-sm-9">{{ optional($report->incident_date)->format('d M Y') ?? '—' }}</dd>

            <dt class="col-sm-3">Submitted</dt>
            <dd class="col-sm-9">{{ optional($report->created_at)->format('d M Y H:i') }}</dd>

            <dt class="col-sm-3">Reporter</dt>
            <dd class="col-sm-9">
              @if ($report->anonymous)
                <span class="badge text-bg-secondary"><i class="bi bi-incognito"></i> Anonymous</span>
              @elseif ($report->user)
                {{ $report->user->name }} <span class="text-secondary">({{ $report->user->email }})</span>
              @else
                —
              @endif
            </dd>

            <dt class="col-sm-3">Description</dt>
            <dd class="col-sm-9" style="white-space: pre-line;">{{ $report->description ?: '—' }}</dd>
          </dl>
        </div>
      </div>

      {{-- Evidence --}}
      <div class="card mb-3">
        <div class="card-header"><h3 class="card-title mb-0">Evidence</h3></div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @forelse ($report->evidences as $evidence)
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
        @if ($canUploadEvidence)
          <div class="card-footer">
            <form action="{{ route('evidence.store', $report) }}" method="POST" enctype="multipart/form-data"
                  class="row g-2 align-items-end">
              @csrf
              <div class="col-12 col-md-8">
                <label for="files" class="form-label">Add evidence (staff upload)</label>
                <input type="file" name="files[]" id="files" class="form-control form-control-sm" multiple
                       accept=".pdf,.docx,.jpg,.jpeg,.png,.mp4">
                <div class="form-text">PDF, DOCX, JPG, PNG or MP4 · max {{ config('jiacrs.max_files') }} files.</div>
              </div>
              <div class="col-12 col-md-4">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                  <i class="bi bi-upload"></i> Upload
                </button>
              </div>
            </form>
          </div>
        @endif
      </div>

      {{-- Investigation --}}
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">Investigation</h3>
          @if ($report->investigation && auth()->user()->hasAnyRole(['Super Admin', 'Admin']))
            <form action="{{ route('investigations.destroy', $report->investigation) }}" method="POST"
                  onsubmit="return confirm('Remove this assignment? The report returns to Under Review.');">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline-danger">Remove assignment</button>
            </form>
          @endif
        </div>
        <div class="card-body">
          @if ($report->investigation)
            <dl class="row mb-0">
              <dt class="col-sm-3">Investigator</dt>
              <dd class="col-sm-9">{{ $report->investigation->investigator?->name ?? '—' }}</dd>

              <dt class="col-sm-3">Findings</dt>
              <dd class="col-sm-9" style="white-space: pre-line;">{{ $report->investigation->findings ?: '—' }}</dd>

              <dt class="col-sm-3">Recommendations</dt>
              <dd class="col-sm-9" style="white-space: pre-line;">{{ $report->investigation->recommendations ?: '—' }}</dd>
            </dl>
            @if (auth()->id() === $report->investigation->investigator_id)
              <a href="{{ route('investigations.show', $report->investigation) }}" class="btn btn-sm btn-outline-primary mt-3">
                <i class="bi bi-pencil"></i> Edit findings
              </a>
            @endif
          @else
            <p class="text-secondary mb-0">No investigation has been assigned to this report yet.</p>
          @endif
        </div>
      </div>

      {{-- Status history timeline --}}
      <div class="card mb-3">
        <div class="card-header"><h3 class="card-title mb-0">Status history</h3></div>
        <div class="card-body">
          @forelse ($report->statusHistories as $history)
            <div class="d-flex {{ $loop->last ? '' : 'mb-3' }}">
              <div class="me-3 text-center">
                <i class="bi bi-clock-history text-primary"></i>
              </div>
              <div class="flex-grow-1 border-bottom pb-2">
                <div class="d-flex justify-content-between">
                  <span>
                    @if ($history->from_status)
                      <x-status-badge :status="$history->from_status" />
                      <i class="bi bi-arrow-right mx-1"></i>
                    @endif
                    <x-status-badge :status="$history->to_status" />
                  </span>
                  <small class="text-secondary">{{ optional($history->created_at)->format('d M Y H:i') }}</small>
                </div>
                <div class="small text-secondary mt-1">
                  by {{ $history->changedBy?->name ?? 'System' }}
                  @if ($history->note)
                    — {{ $history->note }}
                  @endif
                </div>
              </div>
            </div>
          @empty
            <p class="text-secondary mb-0">No status history recorded.</p>
          @endforelse
        </div>
      </div>
    </div>

    {{-- ============ Right column ============ --}}
    <div class="col-lg-4">
      {{-- Assign investigator: only while Under Review (BR-07). --}}
      @can('assign', $report)
        <div class="card mb-3 border-primary">
          <div class="card-header text-bg-primary"><h3 class="card-title mb-0">Assign investigator</h3></div>
          <div class="card-body">
            @if ($investigators->isEmpty())
              <p class="text-secondary mb-0">No investigator accounts available.</p>
            @else
              <form action="{{ route('reports.assign', $report) }}" method="POST">
                @csrf
                <div class="mb-3">
                  <label for="investigator_id" class="form-label">Investigator</label>
                  <select name="investigator_id" id="investigator_id" class="form-select @error('investigator_id') is-invalid @enderror" required>
                    <option value="">Choose…</option>
                    @foreach ($investigators as $investigator)
                      <option value="{{ $investigator->id }}" @selected(old('investigator_id') == $investigator->id)>
                        {{ $investigator->name }}
                      </option>
                    @endforeach
                  </select>
                  @error('investigator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary w-100">
                  <i class="bi bi-person-check"></i> Assign &amp; start case
                </button>
              </form>
            @endif
          </div>
        </div>
      @endcan

      {{-- Status / priority management --}}
      @if (count($allowedStatuses) || $canChangePriority)
        <div class="card mb-3">
          <div class="card-header"><h3 class="card-title mb-0">Manage report</h3></div>
          <div class="card-body">
            <form action="{{ route('reports.update', $report) }}" method="POST">
              @csrf
              @method('PATCH')

              @if (count($allowedStatuses))
                <div class="mb-3">
                  <label for="status" class="form-label">Change status to</label>
                  <select name="status" id="status" class="form-select @error('status') is-invalid @enderror">
                    <option value="">Keep current status ({{ ucwords(str_replace('_', ' ', $report->status)) }})</option>
                    @foreach ($allowedStatuses as $status)
                      <option value="{{ $status }}" @selected(old('status') === $status)>
                        {{ ucwords(str_replace('_', ' ', $status)) }}
                      </option>
                    @endforeach
                  </select>
                  @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="form-text">
                    Allowed next step(s): {{ collect($allowedStatuses)->map(fn ($s) => ucwords(str_replace('_', ' ', $s)))->join(', ') }}.
                  </div>
                </div>

                <div class="mb-3">
                  <label for="note" class="form-label">Internal note</label>
                  <textarea name="note" id="note" rows="2" class="form-control @error('note') is-invalid @enderror"
                            maxlength="1000" placeholder="Reason for the status change (staff only)">{{ old('note') }}</textarea>
                  @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
              @endif

              @if ($canChangePriority)
                <div class="mb-3">
                  <label for="priority" class="form-label">Priority (admin)</label>
                  <select name="priority" id="priority" class="form-select @error('priority') is-invalid @enderror">
                    @foreach (\App\Models\Report::PRIORITIES as $priority)
                      <option value="{{ $priority }}" @selected($report->priority === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                  </select>
                  @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                  <div class="form-text">Set automatically from the category; only admins override it (BR-10).</div>
                </div>
              @endif

              <button type="submit" class="btn btn-primary w-100">
                <i class="bi bi-save"></i> Save changes
              </button>
            </form>
          </div>
        </div>
      @else
        <div class="alert alert-secondary">
          <i class="bi bi-lock"></i> No status changes are available for your role at this stage.
        </div>
      @endif

      @can('delete', $report)
        <div class="card border-danger">
          <div class="card-header text-bg-danger"><h3 class="card-title mb-0">Danger zone</h3></div>
          <div class="card-body">
            <p class="small text-secondary">Deleting a report is a soft delete; it stays in the audit log.</p>
            <form action="{{ route('reports.destroy', $report) }}" method="POST"
                  onsubmit="return confirm('Soft-delete this report?');">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-outline-danger w-100">
                <i class="bi bi-trash"></i> Delete report
              </button>
            </form>
          </div>
        </div>
      @endcan
    </div>
  </div>
@endsection
