@extends('layouts.admin')

@section('title', 'Analytics')
@section('page_title', 'Analytics')
@section('breadcrumb')
  <li class="breadcrumb-item active" aria-current="page">Analytics</li>
@endsection

@section('content')
<div class="card mb-4">
  <div class="card-body">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-3">
        <label class="form-label" for="from">From</label>
        <input type="date" id="from" name="from" value="{{ $filters['from'] }}" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="to">To</label>
        <input type="date" id="to" name="to" value="{{ $filters['to'] }}" class="form-control">
      </div>
      <div class="col-md-3">
        <label class="form-label" for="department_id">Department</label>
        <select id="department_id" name="department_id" class="form-select">
          <option value="">All departments</option>
          @foreach ($departmentList as $d)
            <option value="{{ $d->id }}" @selected($filters['department_id'] == $d->id)>{{ $d->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-primary flex-fill">Apply</button>
        <a href="{{ route('analytics.index') }}" class="btn btn-outline-secondary">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="row">
  <div class="col-md-3 col-6"><div class="card mb-4"><div class="card-body">
    <div class="text-secondary">Reports received</div><div class="fs-2 fw-semibold">{{ $stats['total'] }}</div>
  </div></div></div>
  <div class="col-md-3 col-6"><div class="card mb-4"><div class="card-body">
    <div class="text-secondary">Open cases</div><div class="fs-2 fw-semibold">{{ $stats['open'] }}</div>
    <small class="text-danger">{{ $stats['high_open'] }} high risk</small>
  </div></div></div>
  <div class="col-md-3 col-6"><div class="card mb-4"><div class="card-body">
    <div class="text-secondary">Resolution rate</div><div class="fs-2 fw-semibold">{{ $stats['resolution_rate'] }}%</div>
    <small class="text-secondary">{{ $stats['resolved'] }} resolved or closed, {{ $stats['rejected'] }} rejected</small>
  </div></div></div>
  <div class="col-md-3 col-6"><div class="card mb-4"><div class="card-body">
    <div class="text-secondary">Average time to resolve</div>
    <div class="fs-2 fw-semibold">{{ $stats['avg_days'] !== null ? $stats['avg_days'] . ' days' : '—' }}</div>
  </div></div></div>
</div>

<div class="row">
  <div class="col-lg-8">
    <div class="card mb-4">
      <div class="card-header"><h3 class="card-title">Reports per month</h3></div>
      <div class="card-body"><div class="position-relative" style="height:300px"><canvas id="trendChart" role="img" aria-label="Reports per month"></canvas></div></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-header"><h3 class="card-title">By status</h3></div>
      <div class="card-body"><div class="position-relative" style="height:300px"><canvas id="statusChart" role="img" aria-label="Reports by status"></canvas></div></div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-lg-7">
    <div class="card mb-4">
      <div class="card-header"><h3 class="card-title">By category</h3></div>
      <div class="card-body"><div class="position-relative" style="height:300px"><canvas id="categoryChart" role="img" aria-label="Reports by category"></canvas></div></div>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="card mb-4">
      <div class="card-header"><h3 class="card-title">By priority</h3></div>
      <div class="card-body"><div class="position-relative" style="height:300px"><canvas id="priorityChart" role="img" aria-label="Reports by priority"></canvas></div></div>
    </div>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header"><h3 class="card-title">Department statistics</h3></div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Department</th><th class="text-end">Reports</th><th class="text-end">Resolved / closed</th><th class="text-end">High risk</th></tr></thead>
      <tbody>
        @forelse ($departments as $d)
          <tr>
            <td>{{ $d->name }}</td>
            <td class="text-end">{{ $d->total }}</td>
            <td class="text-end">{{ $d->resolved }}</td>
            <td class="text-end">{{ $d->high_risk }}</td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-center text-secondary py-4">No data for this period.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
  <script src="{{ asset('adminlte/js/chart-theme.js') }}"></script>
  <script>
    const { areaFill } = globalThis.lteChartTheme;
    const c = @json($charts);
    const palette = ['#0d6efd', '#20c997', '#ffc107', '#fd7e14', '#6f42c1', '#198754', '#dc3545', '#6c757d'];

    new Chart(document.querySelector('#trendChart'), {
      type: 'line',
      data: { labels: c.trend.labels, datasets: [
        { label: 'All reports', data: c.trend.all, borderColor: '#0d6efd', backgroundColor: areaFill('#0d6efd'), fill: true, tension: 0.4 },
        { label: 'High risk',   data: c.trend.high, borderColor: '#dc3545', backgroundColor: areaFill('#dc3545', 0.2), fill: true, tension: 0.4 },
      ]},
      options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
    });

    new Chart(document.querySelector('#statusChart'), {
      type: 'doughnut',
      data: { labels: c.status.labels, datasets: [{ data: c.status.data,
        backgroundColor: ['#6c757d', '#0dcaf0', '#0d6efd', '#ffc107', '#198754', '#212529', '#dc3545'] }] },
    });

    new Chart(document.querySelector('#categoryChart'), {
      type: 'bar',
      data: { labels: c.category.labels, datasets: [{ label: 'Reports', data: c.category.data, backgroundColor: palette }] },
      options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } },
    });

    new Chart(document.querySelector('#priorityChart'), {
      type: 'doughnut',
      data: { labels: c.priority.labels, datasets: [{ data: c.priority.data,
        backgroundColor: ['#198754', '#0dcaf0', '#ffc107', '#dc3545'] }] },
    });
  </script>
@endpush
