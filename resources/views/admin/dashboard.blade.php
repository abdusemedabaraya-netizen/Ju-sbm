@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('breadcrumb')
  <li class="breadcrumb-item active" aria-current="page">Dashboard</li>
@endsection

@section('content')
  {{-- ===== Stat cards ===== --}}
  <div class="row">
    <div class="col-lg-3 col-6">
      <div class="small-box text-bg-primary">
        <div class="inner"><h3>{{ $stats['total'] }}</h3><p>Total Reports</p></div>
        <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.75c1.035 0 1.875-.84 1.875-1.875V4.125c0-1.036-.84-1.875-1.875-1.875h-.75zM9.75 8.625c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v11.25c0 1.035-.84 1.875-1.875 1.875h-.75a1.875 1.875 0 01-1.875-1.875V8.625zM3 13.125c0-1.036.84-1.875 1.875-1.875h.75c1.036 0 1.875.84 1.875 1.875v6.75c0 1.035-.84 1.875-1.875 1.875h-.75A1.875 1.875 0 013 19.875v-6.75z"></path>
        </svg>
        <a href="{{ route('reports.index') }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
          View all <i class="bi bi-link-45deg"></i>
        </a>
      </div>
    </div>

    <div class="col-lg-3 col-6">
      <div class="small-box text-bg-warning">
        <div class="inner"><h3>{{ $stats['open'] }}</h3><p>Open Cases</p></div>
        <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25zM12.75 6a.75.75 0 00-1.5 0v6c0 .414.336.75.75.75h4.5a.75.75 0 000-1.5h-3.75V6z"></path>
        </svg>
        <a href="{{ route('reports.index', ['view' => 'open']) }}" class="small-box-footer link-dark link-underline-opacity-0 link-underline-opacity-50-hover">
          View open <i class="bi bi-link-45deg"></i>
        </a>
      </div>
    </div>

    <div class="col-lg-3 col-6">
      <div class="small-box text-bg-success">
        <div class="inner"><h3>{{ $stats['resolved'] }}</h3><p>Resolved Reports</p></div>
        <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z"></path>
        </svg>
        <a href="{{ route('reports.index', ['view' => 'resolved_closed']) }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
          View resolved <i class="bi bi-link-45deg"></i>
        </a>
      </div>
    </div>

    <div class="col-lg-3 col-6">
      <div class="small-box text-bg-danger">
        <div class="inner"><h3>{{ $stats['high_risk'] }}</h3><p>High Risk Cases</p></div>
        <svg class="small-box-icon" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path fill-rule="evenodd" clip-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z"></path>
        </svg>
        <a href="{{ route('reports.index', ['view' => 'high_risk']) }}" class="small-box-footer link-light link-underline-opacity-0 link-underline-opacity-50-hover">
          View high risk <i class="bi bi-link-45deg"></i>
        </a>
      </div>
    </div>
  </div>

  {{-- ===== Charts ===== --}}
  <div class="row">
    <div class="col-lg-7">
      <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Monthly Report Trend (last 6 months)</h3></div>
        <div class="card-body">
          <div class="position-relative" style="height: 300px">
            <canvas id="trend-chart" role="img" aria-label="Line chart of reports submitted per month"></canvas>
          </div>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card mb-4">
        <div class="card-header"><h3 class="card-title">Reports by Category</h3></div>
        <div class="card-body">
          <div class="position-relative" style="height: 300px">
            <canvas id="category-chart" role="img" aria-label="Doughnut chart of reports by category"></canvas>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== Latest reports ===== --}}
  <div class="card mb-4">
    <div class="card-header">
      <h3 class="card-title">Latest Reports</h3>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Tracking No.</th><th>Subject</th><th>Category</th>
              <th>Priority</th><th>Status</th><th>Submitted</th><th></th>
            </tr>
          </thead>
          <tbody>
            @forelse ($latest as $r)
              <tr>
                <td><code>{{ $r->tracking_number }}</code></td>
                <td>{{ \Illuminate\Support\Str::limit($r->subject, 45) }}</td>
                <td>{{ $r->category }}</td>
                <td><x-priority-badge :priority="$r->priority" /></td>
                <td><x-status-badge :status="$r->status" /></td>
                <td>{{ \Illuminate\Support\Carbon::parse($r->created_at)->format('d M Y') }}</td>
                <td class="text-end"><a href="{{ route('reports.show', $r->id) }}" class="btn btn-sm btn-outline-primary">Open</a></td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-secondary py-4">No reports yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
  <script src="{{ asset('adminlte/js/chart-theme.js') }}"></script>
  <script>
    const { areaFill } = globalThis.lteChartTheme;
    const trend = @json($trend);
    const categories = @json($byCategory);

    new Chart(document.querySelector('#trend-chart'), {
      type: 'line',
      data: {
        labels: trend.labels,
        datasets: [{
          label: 'Reports',
          data: trend.data,
          borderColor: '#0d6efd',
          backgroundColor: areaFill('#0d6efd'),
          pointBackgroundColor: '#0d6efd',
          fill: true,
          tension: 0.4,
        }],
      },
      options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
      },
    });

    new Chart(document.querySelector('#category-chart'), {
      type: 'doughnut',
      data: {
        labels: categories.labels,
        datasets: [{
          data: categories.data,
          backgroundColor: ['#0d6efd', '#dc3545', '#ffc107', '#20c997', '#6f42c1', '#fd7e14', '#0dcaf0', '#6c757d'],
        }],
      },
    });
  </script>
@endpush