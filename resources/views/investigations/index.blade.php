@extends('layouts.admin')

@section('title', 'Investigations')
@section('page_title', 'Investigations')
@section('breadcrumb')
  <li class="breadcrumb-item active" aria-current="page">Investigations</li>
@endsection

@php
  $isAdmin = auth()->user()?->hasAnyRole(['Super Admin', 'Admin']);
  $hasFilters = collect(request()->only(['status', 'priority', 'search']))
    ->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
  $tabStatuses = $tab === 'completed'
    ? ['resolved', 'closed']
    : ['assigned', 'investigating'];
@endphp

@section('content')
  {{-- Tabs: Active cases vs. Completed cases. --}}
  <ul class="nav nav-tabs mb-3">
    <li class="nav-item">
      <a class="nav-link {{ $tab !== 'completed' ? 'active' : '' }}"
         href="{{ route('investigations.index', array_filter(['status' => request('status'), 'priority' => request('priority'), 'search' => request('search')])) }}">
        Active
        <span class="badge text-bg-secondary ms-1">{{ $caseCounts['active'] ?? 0 }}</span>
      </a>
    </li>
    <li class="nav-item">
      <a class="nav-link {{ $tab === 'completed' ? 'active' : '' }}"
         href="{{ route('investigations.index', array_filter(['tab' => 'completed', 'status' => request('status'), 'priority' => request('priority'), 'search' => request('search')])) }}">
        Completed
        <span class="badge text-bg-success ms-1">{{ $caseCounts['completed'] ?? 0 }}</span>
      </a>
    </li>
  </ul>

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0">
        {{ $tab === 'completed' ? 'Completed cases' : ($isAdmin ? 'Active cases' : 'My assigned cases') }}
      </h3>
      @if ($isAdmin)
        <a href="{{ route('investigations.create') }}" class="btn btn-sm btn-primary">
          <i class="bi bi-person-plus"></i> Assign Investigator
        </a>
      @endif
    </div>
    <div class="card-body border-bottom">
      <form action="{{ route('investigations.index') }}" method="GET" class="row g-2 align-items-end">
        @if ($tab === 'completed')
          <input type="hidden" name="tab" value="completed">
        @endif

        <div class="col-12 col-md-5 col-lg-4">
          <label for="search" class="form-label">Search</label>
          <input type="search" name="search" id="search" value="{{ request('search') }}"
                 class="form-control form-control-sm" placeholder="Tracking no. or subject">
        </div>

        <div class="col-6 col-md-3 col-lg-2">
          <label for="status" class="form-label">Status</label>
          <select name="status" id="status" class="form-select form-select-sm">
            <option value="">Any status</option>
            @foreach ($tabStatuses as $status)
              <option value="{{ $status }}" @selected(request('status') === $status)>
                {{ ucwords(str_replace('_', ' ', $status)) }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-3 col-lg-2">
          <label for="priority" class="form-label">Priority</label>
          <select name="priority" id="priority" class="form-select form-select-sm">
            <option value="">Any priority</option>
            @foreach (\App\Models\Report::PRIORITIES as $priority)
              <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-lg-4 d-flex gap-2">
          <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filter</button>
          @if ($hasFilters)
            <a href="{{ route('investigations.index', $tab === 'completed' ? ['tab' => 'completed'] : []) }}"
               class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Tracking No.</th>
              <th>Subject</th>
              @if ($isAdmin)<th>Investigator</th>@endif
              <th>Priority</th>
              <th>Status</th>
              <th>Assigned</th>
              <th>Age</th>
              <th class="text-end"></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($investigations as $investigation)
              @php
                $report = $investigation->report;
                $daysOpen = $investigation->created_at?->diffInDays(now());
                $overdue = $tab !== 'completed' && $daysOpen !== null && $daysOpen > 30;
              @endphp
              <tr class="{{ $overdue ? 'table-warning' : '' }}">
                <td><code>{{ $report?->tracking_number }}</code></td>
                <td>{{ \Illuminate\Support\Str::limit($report?->subject, 45) }}</td>
                @if ($isAdmin)
                  <td>{{ $investigation->investigator?->name ?? '—' }}</td>
                @endif
                <td>@if ($report)<x-priority-badge :priority="$report->priority" />@endif</td>
                <td>@if ($report)<x-status-badge :status="$report->status" />@endif</td>
                <td>{{ optional($investigation->created_at)->format('d M Y') }}</td>
                <td>
                  @if ($daysOpen !== null)
                    <span class="badge text-bg-{{ $overdue ? 'danger' : 'light' }}"
                          @if ($overdue) title="Open for more than 30 days" @endif>
                      {{ $daysOpen }}d
                    </span>
                  @else
                    —
                  @endif
                </td>
                <td class="text-end">
                  <a href="{{ route('investigations.show', $investigation) }}" class="btn btn-sm btn-outline-primary">Open</a>
                  @if ($isAdmin)
                    <form action="{{ route('investigations.destroy', $investigation) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Remove this assignment? The report returns to Under Review.');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">Remove</button>
                    </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="{{ $isAdmin ? 8 : 7 }}">
                  <div class="text-center text-secondary py-5">
                    <i class="bi bi-folder2-open fs-1 d-block mb-2"></i>
                    <p class="mb-1 fw-semibold">No cases found</p>
                    <p class="mb-3 small">
                      @if ($hasFilters)
                        No case matches the current filters.
                      @elseif ($tab === 'completed')
                        No case has been resolved or closed yet.
                      @else
                        There are no active cases.
                      @endif
                    </p>
                    @if ($hasFilters)
                      <a href="{{ route('investigations.index', $tab === 'completed' ? ['tab' => 'completed'] : []) }}"
                         class="btn btn-sm btn-outline-secondary">Clear filters</a>
                    @endif
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if ($investigations->hasPages())
      <div class="card-footer">
        {{-- withQueryString() on the paginator keeps the tab and filters. --}}
        {{ $investigations->links() }}
      </div>
    @endif
  </div>
@endsection
