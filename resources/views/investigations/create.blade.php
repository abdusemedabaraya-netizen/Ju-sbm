@extends('layouts.admin')

@section('title', 'Assign Investigator')
@section('page_title', 'Assign Investigator')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('investigations.index') }}">Investigations</a></li>
  <li class="breadcrumb-item active" aria-current="page">Assign</li>
@endsection

@section('content')
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title mb-0">Assign an investigator to a report</h3>
        </div>
        <div class="card-body">
          @if ($errors->any())
            <div class="alert alert-danger" role="alert">
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <p class="text-secondary small">
            Only reports that are <strong>Under Review</strong> and not yet assigned can be given to an
            investigator (BR-07). Assigning moves the report to <strong>Assigned</strong>.
          </p>

          <form action="{{ route('investigations.store') }}" method="POST" id="assign-form">
            @csrf

            <div class="mb-3">
              <label for="report_id" class="form-label">Report <span class="text-danger">*</span></label>
              <select name="report_id" id="report_id" class="form-select @error('report_id') is-invalid @enderror" required>
                <option value="">— Select a report —</option>
                @foreach ($reports as $report)
                  <option value="{{ $report->id }}" data-user-id="{{ $report->user_id }}"
                          @selected(old('report_id') == $report->id)>
                    {{ $report->tracking_number }} — {{ $report->subject }}
                    ({{ ucfirst($report->priority) }})
                  </option>
                @endforeach
              </select>
              @error('report_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              @if ($reports->isEmpty())
                <div class="form-text text-warning">
                  No report is currently Under Review. Move a submitted report to Under Review first.
                </div>
              @endif
            </div>

            <div class="mb-3">
              <label for="investigator_id" class="form-label">Investigator <span class="text-danger">*</span></label>
              <select name="investigator_id" id="investigator_id" class="form-select @error('investigator_id') is-invalid @enderror" required>
                <option value="">— Select an investigator —</option>
                @foreach ($investigators as $investigator)
                  <option value="{{ $investigator->id }}" data-user-id="{{ $investigator->id }}"
                          @selected(old('investigator_id') == $investigator->id)>
                    {{ $investigator->name }} — {{ $investigator->open_cases_count }} open case(s)
                  </option>
                @endforeach
              </select>
              @error('investigator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
              <div class="form-text" id="conflict-warning" style="display:none;">
                <span class="text-danger">
                  <i class="bi bi-exclamation-triangle"></i> This investigator submitted the selected report
                  and cannot handle it (conflict of interest).
                </span>
              </div>
            </div>

            <div class="d-flex gap-2">
              <button type="submit" class="btn btn-primary" id="assign-btn">
                <i class="bi bi-person-check"></i> Assign &amp; start case
              </button>
              <a href="{{ route('investigations.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-header"><h3 class="card-title mb-0">Investigator workload</h3></div>
        <div class="card-body p-0">
          <ul class="list-group list-group-flush">
            @forelse ($investigators as $investigator)
              <li class="list-group-item d-flex justify-content-between align-items-center">
                {{ $investigator->name }}
                <span class="badge text-bg-{{ $investigator->open_cases_count > 0 ? 'primary' : 'secondary' }}">
                  {{ $investigator->open_cases_count }} open
                </span>
              </li>
            @empty
              <li class="list-group-item text-center text-secondary py-4">No investigator accounts.</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  (function () {
    var report = document.getElementById('report_id');
    var investigator = document.getElementById('investigator_id');
    var warning = document.getElementById('conflict-warning');
    var button = document.getElementById('assign-btn');
    if (!report || !investigator) return;

    var options = Array.prototype.slice.call(investigator.querySelectorAll('option[data-user-id]'));

    function refresh() {
      var submitter = report.options[report.selectedIndex]
        ? report.options[report.selectedIndex].getAttribute('data-user-id')
        : null;

      var conflict = false;
      options.forEach(function (option) {
        var isSubmitter = submitter && option.getAttribute('data-user-id') === submitter;
        option.disabled = isSubmitter;
        if (isSubmitter && investigator.value === option.value) {
          investigator.value = '';
          conflict = true;
        }
      });

      warning.style.display = conflict ? 'block' : 'none';
      button.disabled = conflict;
    }

    report.addEventListener('change', refresh);
    investigator.addEventListener('change', function () {
      warning.style.display = 'none';
      button.disabled = false;
    });
    refresh();
  })();
</script>
@endpush
