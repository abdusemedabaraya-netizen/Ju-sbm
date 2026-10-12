@extends('layouts.admin')

@section('title', 'My Assigned Cases')
@section('page_title', 'My Assigned Cases')
@section('breadcrumb')
  <li class="breadcrumb-item active" aria-current="page">My Cases</li>
@endsection

@php
  $statusBadge = [
    'assigned' => 'primary', 'investigating' => 'warning', 'resolved' => 'success', 'closed' => 'dark',
  ];
  $boxes = [
    ['total', 'Total Cases', 'bi-folder2-open', 'bg-primary'],
    ['assigned', 'New / Assigned', 'bi-hourglass-split', 'bg-info'],
    ['ongoing', 'In Progress', 'bi-search', 'bg-warning'],
    ['resolved', 'Resolved', 'bi-check-circle', 'bg-success'],
  ];
@endphp

@section('content')
  <div class="row">
    @foreach ($boxes as [$key, $label, $icon, $color])
      <div class="col-sm-6 col-lg-3">
        <div class="small-box {{ $color }}">
          <div class="inner">
            <h3>{{ $stats[$key] }}</h3>
            <p>{{ $label }}</p>
          </div>
          <div class="icon"><i class="bi {{ $icon }}"></i></div>
        </div>
      </div>
    @endforeach
  </div>

  <div class="card mt-3">
    <div class="card-header"><h3 class="card-title">Assigned to me</h3></div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Tracking No.</th>
              <th>Subject</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Assigned</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($cases as $case)
              <tr>
                <td><code>{{ $case->report?->tracking_number }}</code></td>
                <td>{{ \Illuminate\Support\Str::limit($case->report?->subject, 45) }}</td>
                <td><span class="badge text-bg-secondary text-capitalize">{{ $case->report?->priority ?? '—' }}</span></td>
                <td>
                  <span class="badge text-bg-{{ $statusBadge[$case->report?->status] ?? 'secondary' }}">
                    {{ ucwords(str_replace('_', ' ', $case->report?->status ?? 'n/a')) }}
                  </span>
                </td>
                <td>{{ optional($case->created_at)->format('d M Y') }}</td>
                <td class="text-end">
                  <a href="{{ route('investigations.show', $case) }}" class="btn btn-sm btn-outline-primary">Open Case</a>
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-secondary py-4">No cases assigned to you yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection