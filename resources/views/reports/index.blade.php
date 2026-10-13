@extends('layouts.admin')

@section('title', 'Reports')
@section('page_title', 'Reports')
@section('breadcrumb')
  <li class="breadcrumb-item active" aria-current="page">Reports</li>
@endsection

@php
  $activeView = request('view');
  $hasFilters = collect(request()->only([
    'search', 'status', 'priority', 'department_id', 'category', 'date_from', 'date_to', 'view',
  ]))->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty();
@endphp

@section('content')
  <div class="card mb-4">
    <div class="card-header">
      <h3 class="card-title">Filter reports</h3>
    </div>
    <div class="card-body">
      <form action="{{ route('reports.index') }}" method="GET" class="row g-2 align-items-end">
        @if ($activeView)
          <input type="hidden" name="view" value="{{ $activeView }}">
        @endif

        <div class="col-12 col-md-6 col-xl-3">
          <label for="search" class="form-label">Search</label>
          <input type="search" name="search" id="search" value="{{ request('search') }}"
                 class="form-control form-control-sm" placeholder="Tracking no. or subject">
        </div>

        <div class="col-6 col-md-3 col-xl-2">
          <label for="status" class="form-label">Status</label>
          <select name="status" id="status" class="form-select form-select-sm">
            <option value="">Any status</option>
            @foreach (\App\Models\Report::STATUSES as $status)
              <option value="{{ $status }}" @selected(request('status') === $status)>
                {{ ucwords(str_replace('_', ' ', $status)) }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-3 col-xl-2">
          <label for="priority" class="form-label">Priority</label>
          <select name="priority" id="priority" class="form-select form-select-sm">
            <option value="">Any priority</option>
            @foreach (\App\Models\Report::PRIORITIES as $priority)
              <option value="{{ $priority }}" @selected(request('priority') === $priority)>{{ ucfirst($priority) }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-3 col-xl-2">
          <label for="department_id" class="form-label">Department</label>
          <select name="department_id" id="department_id" class="form-select form-select-sm">
            <option value="">Any department</option>
            @foreach ($departments as $department)
              <option value="{{ $department->id }}" @selected((string) request('department_id') === (string) $department->id)>
                {{ $department->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-3 col-xl-2">
          <label for="category" class="form-label">Category</label>
          <select name="category" id="category" class="form-select form-select-sm">
            <option value="">Any category</option>
            @foreach ($categories as $category)
              <option value="{{ $category->slug }}" @selected(request('category') === $category->slug)>{{ $category->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-6 col-md-3 col-xl-2">
          <label for="date_from" class="form-label">Submitted from</label>
          <input type="date" name="date_from" id="date_from" value="{{ request('date_from') }}" class="form-control form-control-sm">
        </div>

        <div class="col-6 col-md-3 col-xl-2">
          <label for="date_to" class="form-label">Submitted to</label>
          <input type="date" name="date_to" id="date_to" value="{{ request('date_to') }}" class="form-control form-control-sm">
        </div>

        <div class="col-12 col-xl-2 d-flex gap-2">
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="bi bi-funnel"></i> Filter
          </button>
          @if ($hasFilters)
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h3 class="card-title mb-0">
        @switch($activeView)
          @case('new') New reports @break
          @case('under_review') Under review @break
          @case('in_progress') In progress @break
          @case('high_risk') High risk @break
          @case('resolved_closed') Resolved &amp; closed @break
          @case('rejected') Rejected @break
          @default All Reports
        @endswitch
      </h3>
      <span class="text-secondary small">{{ $reports->total() }} report(s)</span>
    </div>

    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Tracking No.</th>
              <th>Subject</th>
              <th>Department</th>
              <th>Category</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Submitted</th>
              <th class="text-end"></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($reports as $report)
              <tr>
                <td><code>{{ $report->tracking_number }}</code></td>
                <td>
                  {{ \Illuminate\Support\Str::limit($report->subject, 50) }}
                  @if ($report->anonymous)
                    <span class="badge text-bg-secondary ms-1" title="Reporter identity withheld">
                      <i class="bi bi-incognito"></i> Anonymous
                    </span>
                  @endif
                </td>
                <td>{{ $report->department?->name ?? '—' }}</td>
                <td>{{ ucwords(str_replace('_', ' ', $report->category)) }}</td>
                <td><x-priority-badge :priority="$report->priority" /></td>
                <td><x-status-badge :status="$report->status" /></td>
                <td>{{ optional($report->created_at)->format('d M Y') }}</td>
                <td class="text-end">
                  <a href="{{ route('reports.show', $report) }}" class="btn btn-sm btn-outline-primary">Open</a>
                  @can('delete', $report)
                    <form action="{{ route('reports.destroy', $report) }}" method="POST" class="d-inline"
                          onsubmit="return confirm('Delete this report? It is only soft-deleted and stays logged.');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                  @endcan
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8">
                  <div class="text-center text-secondary py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    <p class="mb-1 fw-semibold">No reports found</p>
                    <p class="mb-3 small">
                      @if ($hasFilters)
                        No report matches the current filters.
                      @else
                        There are no reports to display yet.
                      @endif
                    </p>
                    @if ($hasFilters)
                      <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary">Clear filters</a>
                    @endif
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if ($reports->hasPages())
      <div class="card-footer">
        {{-- withQueryString() on the paginator keeps every active filter. --}}
        {{ $reports->links() }}
      </div>
    @endif
  </div>
@endsection
