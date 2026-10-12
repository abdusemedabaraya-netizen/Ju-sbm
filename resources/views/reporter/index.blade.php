@extends('layouts.admin')

@section('title', 'My Reports')
@section('page_title', 'My Reports')
@section('breadcrumb')
  <li class="breadcrumb-item active" aria-current="page">My Reports</li>
@endsection

@php
  $statusBadge = [
    'submitted' => 'secondary', 'under_review' => 'info', 'assigned' => 'primary',
    'investigating' => 'warning', 'resolved' => 'success', 'closed' => 'dark', 'rejected' => 'danger',
  ];
  $priorityBadge = ['low' => 'success', 'medium' => 'info', 'high' => 'warning', 'critical' => 'danger'];
@endphp

@section('content')
  <div class="card mb-4">
    <div class="card-header">
      <h3 class="card-title">Reports I Submitted</h3>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Tracking No.</th>
              <th>Subject</th>
              <th>Department</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Submitted</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($reports as $report)
              <tr>
                <td><code>{{ $report->tracking_number }}</code></td>
                <td>{{ \Illuminate\Support\Str::limit($report->subject, 45) }}</td>
                <td>{{ $report->department?->name ?? '—' }}</td>
                <td>
                  <span class="badge text-bg-{{ $priorityBadge[$report->priority] ?? 'secondary' }}">
                    {{ ucfirst($report->priority) }}
                  </span>
                </td>
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
              <tr>
                <td colspan="7" class="text-center text-secondary py-4">You have not submitted any reports yet.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if ($reports->hasPages())
      <div class="card-footer">
        {{ $reports->links() }}
      </div>
    @endif
  </div>
@endsection
