@extends('layouts.admin')

@section('title', 'Case Details')
@section('page_title', 'Case Details')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('investigations.index') }}">Investigations</a></li>
  <li class="breadcrumb-item active" aria-current="page">{{ $investigation->report?->tracking_number ?? 'Details' }}</li>
@endsection

@php
  $isAdmin = auth()->user()?->hasAnyRole(['Super Admin', 'Admin']);
  $report  = $investigation->report;
  $statusBadge = [
    'submitted' => 'secondary', 'under_review' => 'info', 'assigned' => 'primary',
    'investigating' => 'warning', 'resolved' => 'success', 'closed' => 'dark', 'rejected' => 'danger',
  ];
  $priorityBadge = ['low' => 'success', 'medium' => 'info', 'high' => 'warning', 'critical' => 'danger'];
  $investBadge  = ['pending' => 'secondary', 'ongoing' => 'warning', 'completed' => 'success'];
@endphp

@section('content')
  <div class="row g-3 mb-3">
    <div class="col-lg-8">

      {{-- Case / Report details --}}
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">{{ $report?->subject ?? 'Report Details' }}</h3>
          @if ($report)
            <span class="text-secondary"><code>{{ $report->tracking_number }}</code></span>
          @endif
        </div>
        <div class="card-body">
          @if ($report)
            <dl class="row mb-0">
              <dt class="col-sm-3">Category</dt>
              <dd class="col-sm-9">{{ ucwords(str_replace('_', ' ', $report->category)) }}</dd>

              <dt class="col-sm-3">Department</dt>
              <dd class="col-sm-9">{{ $report->department?->name ?? '—' }}</dd>

              <dt class="col-sm-3">Priority</dt>
              <dd class="col-sm-9">
                <span class="badge text-bg-{{ $priorityBadge[$report->priority] ?? 'secondary' }}">{{ ucfirst($report->priority) }}</span>
              </dd>

              <dt class="col-sm-3">Status</dt>
              <dd class="col-sm-9">
                <span class="badge text-bg-{{ $statusBadge[$report->status] ?? 'secondary' }}">
                  {{ ucwords(str_replace('_', ' ', $report->status)) }}
                </span>
              </dd>

              <dt class="col-sm-3">Location</dt>
              <dd class="col-sm-9">{{ $report->location ?? '—' }}</dd>

              <dt class="col-sm-3">Incident Date</dt>
              <dd class="col-sm-9">{{ optional($report->incident_date)->format('d M Y') ?? '—' }}</dd>

              <dt class="col-sm-3">Submitted</dt>
              <dd class="col-sm-9">{{ optional($report->created_at)->format('d M Y H:i') }}</dd>

              <dt class="col-sm-3">Reporter</dt>
              <dd class="col-sm-9">
                @if ($report->anonymous)
                  <span class="badge text-bg-secondary">Anonymous</span>
                @elseif ($report->user)
                  {{ $report->user->name }} ({{ $report->user->email }})
                @else
                  —
                @endif
              </dd>

              <dt class="col-sm-3">Description</dt>
              <dd class="col-sm-9">{{ $report->description ?: '—' }}</dd>
            </dl>
          @else
            <p class="text-secondary mb-0">The related report could not be found.</p>
          @endif
        </div>
      </div>

      {{-- Status history --}}
      <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Status History</h3></div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @forelse ($report?->statusHistory ?? [] as $h)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                  <i class="bi bi-arrow-right-circle"></i>
                  @if ($h->from_status)
                    {{ ucwords(str_replace('_', ' ', $h->from_status)) }}
                    <i class="bi bi-arrow-right"></i>
                  @endif
                  <strong>{{ ucwords(str_replace('_', ' ', $h->to_status)) }}</strong>
                  @if ($h->note)
                    <small class="d-block text-secondary">{{ $h->note }}</small>
                  @endif
                </span>
                <small class="text-secondary">{{ optional($h->created_at)->format('d M Y H:i') }}</small>
              </li>
            @empty
              <li class="list-group-item text-center text-secondary py-4">No status changes yet.</li>
            @endforelse
          </ul>
        </div>
      </div>

      {{-- Evidence --}}
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">Evidence</h3>
          @if ($report)
            <button type="button" class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="collapse" data-bs-target="#evidenceUpload">
              <i class="bi bi-paperclip"></i> Attach
            </button>
          @endif
        </div>
        <div id="evidenceUpload" class="collapse">
          <div class="card-body border-bottom">
            @if ($report)
              <form action="{{ route('evidence.store', $report) }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="input-group">
                  <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" required>
                  <button type="submit" class="btn btn-primary">Upload</button>
                </div>
                <div class="form-text">PDF, DOC, XLS, images, ZIP up to 10 MB.</div>
                @error('file')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </form>
            @endif
          </div>
        </div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @forelse ($report?->evidences ?? [] as $evidence)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                  <i class="bi bi-paperclip"></i> {{ $evidence->file_name }}
                  <small class="text-secondary">({{ number_format($evidence->file_size / 1024, 1) }} KB)</small>
                </span>
                <a href="{{ route('evidence.download', $evidence) }}" class="btn btn-sm btn-outline-primary">Download</a>
              </li>
            @empty
              <li class="list-group-item text-center text-secondary py-4">No evidence attached.</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>

    <div class="col-lg-4">

      {{-- Assignment --}}
      <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Assignment</h3></div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-5">Investigator</dt>
            <dd class="col-7">{{ $investigation->investigator?->name ?? '—' }}</dd>

            <dt class="col-5">Assigned</dt>
            <dd class="col-7">{{ optional($investigation->created_at)->format('d M Y H:i') }}</dd>

            <dt class="col-5">Investigation</dt>
            <dd class="col-7">
              <span class="badge text-bg-{{ $investBadge[$investigation->status] ?? 'secondary' }}">
                {{ ucfirst($investigation->status) }}
              </span>
            </dd>
          </dl>
        </div>
      </div>

      {{-- Case actions --}}
      @if ($report && in_array($report->status, ['assigned', 'investigating'], true))
        <div class="card mb-3">
          <div class="card-header"><h3 class="card-title">Case Actions</h3></div>
          <div class="card-body d-grid gap-2">
            @if ($report->status === 'assigned')
              <form action="{{ route('investigations.status', $investigation) }}" method="POST">
                @csrf
                <input type="hidden" name="move" value="start">
                <button type="submit" class="btn btn-primary w-100">
                  <i class="bi bi-search"></i> Start Investigation
                </button>
              </form>
            @elseif ($report->status === 'investigating')
              <form action="{{ route('investigations.status', $investigation) }}" method="POST">
                @csrf
                <input type="hidden" name="move" value="resolve">
                <button type="submit" class="btn btn-success w-100">
                  <i class="bi bi-check-circle"></i> Mark Resolved
                </button>
              </form>
            @endif
          </div>
        </div>
      @endif

      {{-- Findings & Recommendations --}}
      <div class="card">
        <div class="card-header"><h3 class="card-title">Findings &amp; Recommendations</h3></div>
        <div class="card-body">
          <form action="{{ route('investigations.update', $investigation) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="mb-3">
              <label for="findings" class="form-label">Findings</label>
              <textarea name="findings" id="findings" rows="6" class="form-control @error('findings') is-invalid @enderror"
                        placeholder="Record investigation findings...">{{ old('findings', $investigation->findings) }}</textarea>
              @error('findings')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <div class="mb-3">
              <label for="recommendation" class="form-label">Recommendation</label>
              <textarea name="recommendation" id="recommendation" rows="5" class="form-control @error('recommendation') is-invalid @enderror"
                        placeholder="Record recommendation...">{{ old('recommendation', $investigation->recommendation) }}</textarea>
              @error('recommendation')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            <button type="submit" class="btn btn-primary w-100">
              <i class="bi bi-save"></i> Save Changes
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
@endsection