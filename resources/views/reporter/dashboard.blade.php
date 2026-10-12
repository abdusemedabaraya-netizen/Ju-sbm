@extends('layouts.admin')

@section('title', 'My Dashboard')
@section('page_title', 'My Dashboard')
@section('breadcrumb')
  <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('content')
  <div class="row">
    @php
      $boxes = [
        ['total',    'Total Reports',     'bi-file-earmark-text', 'bg-primary', route('my.reports.index')],
        ['pending',  'Pending Review',    'bi-hourglass-split',   'bg-warning', route('my.reports.index')],
        ['inprog',   'In Progress',       'bi-search',            'bg-info',    route('my.reports.index')],
        ['resolved', 'Resolved / Closed', 'bi-check-circle',      'bg-success', route('my.reports.index')],
      ];
    @endphp
    @foreach ($boxes as [$key, $label, $icon, $color, $link])
      <div class="col-sm-6 col-lg-3">
        <div class="small-box {{ $color }}">
          <div class="inner">
            <h3>{{ $stats[$key] }}</h3>
            <p>{{ $label }}</p>
          </div>
          <div class="icon"><i class="bi {{ $icon }}"></i></div>
          <a href="{{ $link }}" class="small-box-footer">View reports <i class="bi bi-arrow-right"></i></a>
        </div>
      </div>
    @endforeach
  </div>

  <div class="row mt-3">
    <div class="col-lg-8">
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="card-title mb-0">Recent Reports</h3>
          <a href="{{ route('my.reports.index') }}" class="btn btn-sm btn-outline-secondary">View all</a>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>Tracking No.</th>
                  <th>Subject</th>
                  <th>Status</th>
                  <th>Submitted</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                @php
                  $statusBadge = [
                    'submitted' => 'secondary', 'under_review' => 'info', 'assigned' => 'primary',
                    'investigating' => 'warning', 'resolved' => 'success', 'closed' => 'dark', 'rejected' => 'danger',
                  ];
                @endphp
                @forelse ($recent as $report)
                  <tr>
                    <td><code>{{ $report->tracking_number }}</code></td>
                    <td>{{ \Illuminate\Support\Str::limit($report->subject, 40) }}</td>
                    <td>
                      <span class="badge text-bg-{{ $statusBadge[$report->status] ?? 'secondary' }}">
                        {{ ucwords(str_replace('_', ' ', $report->status)) }}
                      </span>
                    </td>
                    <td>{{ optional($report->created_at)->format('d M Y') }}</td>
                    <td class="text-end">
                      <a href="{{ route('my.reports.show', $report) }}" class="btn btn-sm btn-outline-primary">Open</a>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-secondary py-4">No reports yet.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Quick Actions</h3></div>
        <div class="card-body d-grid gap-2">
          <a href="{{ route('report.create.identified') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Submit a Report
          </a>
          <a href="{{ route('my.reports.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-text me-1"></i> My Reports
          </a>
          <a href="{{ route('notifications.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-bell me-1"></i> Notifications
            @if ($unreadCount)<span class="badge text-bg-warning">{{ $unreadCount }}</span>@endif
          </a>
        </div>
      </div>
    </div>
  </div>
@endsection