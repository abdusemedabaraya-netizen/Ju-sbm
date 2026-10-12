@extends('layouts.admin')

@section('title', 'Report Details')
@section('page_title', 'Report Details')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('my.reports.index') }}">My Reports</a></li>
  <li class="breadcrumb-item active" aria-current="page">{{ $report->tracking_number }}</li>
@endsection

@php
  $statusBadge = [
    'submitted' => 'secondary', 'under_review' => 'info', 'assigned' => 'primary',
    'investigating' => 'warning', 'resolved' => 'success', 'closed' => 'dark', 'rejected' => 'danger',
  ];
  $priorityBadge = ['low' => 'success', 'medium' => 'info', 'high' => 'warning', 'critical' => 'danger'];
@endphp

@section('content')
  <div class="row g-3 mb-3">
    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title">{{ $report->subject }}</h3>
          <span class="text-secondary"><code>{{ $report->tracking_number }}</code></span>
        </div>
        <div class="card-body">
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
              <span class="badge text-bg-{{ $statusBadge[$report->status] ?? 'secondary' }}">{{ ucwords(str_replace('_', ' ', $report->status)) }}</span>
            </dd>

            <dt class="col-sm-3">Location</dt>
            <dd class="col-sm-9">{{ $report->location ?? '—' }}</dd>

            <dt class="col-sm-3">Incident Date</dt>
            <dd class="col-sm-9">{{ optional($report->incident_date)->format('d M Y') ?? '—' }}</dd>

            <dt class="col-sm-3">Submitted</dt>
            <dd class="col-sm-9">{{ optional($report->created_at)->format('d M Y H:i') }}</dd>

            <dt class="col-sm-3">Description</dt>
            <dd class="col-sm-9">{{ $report->description ?: '—' }}</dd>
          </dl>
        </div>
      </div>

      <div class="card mb-3">
        <div class="card-header"><h3 class="card-title">Status History</h3></div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @forelse ($report->statusHistories as $history)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <span>
                  @if ($history->from_status)
                    <span class="badge text-bg-{{ $statusBadge[$history->from_status] ?? 'secondary' }}">
                      {{ ucwords(str_replace('_', ' ', $history->from_status)) }}
                    </span>
                    <i class="bi bi-arrow-right mx-1 text-secondary"></i>
                  @endif
                  <span class="badge text-bg-{{ $statusBadge[$history->to_status] ?? 'secondary' }}">
                    {{ ucwords(str_replace('_', ' ', $history->to_status)) }}
                  </span>
                  @if ($history->note)
                    <span class="ms-2">{{ $history->note }}</span>
                  @endif
                </span>
                <small class="text-secondary">{{ optional($history->created_at)->format('d M Y H:i') }}</small>
              </li>
            @empty
              <li class="list-group-item text-center text-secondary py-4">No status updates yet.</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h3 class="card-title">Actions</h3></div>
        <div class="card-body">
          <a href="{{ route('my.reports.index') }}" class="btn btn-outline-secondary w-100">
            <i class="bi bi-arrow-left"></i> Back to My Reports
          </a>
        </div>
      </div>
    </div>
  </div>
@endsection
