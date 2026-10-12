@extends('layouts.admin')

@section('title', 'Audit log')
@section('page_title', 'Audit log')
@section('breadcrumb')
  <li class="breadcrumb-item active" aria-current="page">Audit log</li>
@endsection

@section('content')
<div class="card mb-4">
  <div class="card-header">
    <form method="GET" class="row g-2">
      <div class="col-md-4">
        <select name="action" class="form-select">
          <option value="">All actions</option>
          @foreach ($actions as $a)
            <option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3"><input type="date" name="from" value="{{ request('from') }}" class="form-control" aria-label="From date"></div>
      <div class="col-md-3"><input type="date" name="to" value="{{ request('to') }}" class="form-control" aria-label="To date"></div>
      <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-primary flex-fill">Filter</button>
        <a href="{{ route('audit-logs.index') }}" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead><tr><th>When</th><th>User</th><th>Action</th><th>Subject</th><th>Details</th><th>IP</th></tr></thead>
      <tbody>
        @forelse ($logs as $log)
          <tr>
            <td class="text-nowrap">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
            <td>{{ $log->user?->name ?? '—' }}</td>
            <td><code>{{ $log->action }}</code></td>
            <td>{{ $log->subject_type ? class_basename($log->subject_type) . ' #' . $log->subject_id : '—' }}</td>
            <td>{{ $log->description ?: '—' }}</td>
            <td>{{ $log->ip_address ?? '—' }}</td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-secondary py-4">No activity recorded yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if ($logs->hasPages())<div class="card-footer">{{ $logs->links() }}</div>@endif
</div>
<p class="text-secondary small">Entries tied to anonymous reports never store a user or an IP address.</p>
@endsection
