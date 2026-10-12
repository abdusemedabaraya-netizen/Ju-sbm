@extends('layouts.admin')

@php
  $isAdmin = auth()->user()?->hasAnyRole(['Super Admin', 'Admin']);
  $statusBadge = [
    'assigned' => 'primary', 'investigating' => 'warning', 'under_review' => 'info',
    'resolved' => 'success', 'closed' => 'dark', 'rejected' => 'danger',
  ];
@endphp

@section('title', $isAdmin ? 'All Investigations' : 'My Assigned Cases')
@section('page_title', $isAdmin ? 'All Investigations' : 'My Assigned Cases')
@section('breadcrumb')
  <li class="breadcrumb-item active" aria-current="page">
    {{ $isAdmin ? 'Investigations' : 'My Cases' }}
  </li>
@endsection

@section('content')
  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0">
        {{ $isAdmin ? 'All case investigations' : 'Cases assigned to me' }}
      </h3>
      @if ($isAdmin)
        <a href="{{ route('investigations.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-plus-lg"></i> Assign Investigator
        </a>
      @endif
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Tracking No.</th>
              <th>Subject</th>
              @if ($isAdmin)
                <th>Investigator</th>
              @endif
              <th>Report Status</th>
              <th>Assigned</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($investigations as $investigation)
              <tr>
                <td><code>{{ $investigation->report?->tracking_number }}</code></td>
                <td>{{ \Illuminate\Support\Str::limit($investigation->report?->subject, 45) }}</td>
                @if ($isAdmin)
                  <td>{{ $investigation->investigator?->name ?? '—' }}</td>
                @endif
                <td>
                  <span class="badge text-bg-{{ $statusBadge[$investigation->report?->status] ?? 'secondary' }}">
                    {{ ucwords(str_replace('_', ' ', $investigation->report?->status ?? 'n/a')) }}
                  </span>
                </td>
                <td>{{ optional($investigation->created_at)->format('d M Y') }}</td>
                <td class="text-end">
                  <a href="{{ route('investigations.show', $investigation) }}" class="btn btn-sm btn-outline-primary">Open</a>
                  @if ($isAdmin)
                    <form action="{{ route('investigations.destroy', $investigation) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Remove this investigation assignment?');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                    </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="{{ $isAdmin ? 6 : 5 }}" class="text-center text-secondary py-4">
                  {{ $isAdmin ? 'No investigations yet.' : 'No cases assigned to you yet.' }}
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
    @if ($investigations->hasPages())
      <div class="card-footer">
        {{ $investigations->links() }}
      </div>
    @endif
  </div>
@endsection