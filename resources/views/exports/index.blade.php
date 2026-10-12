@extends('layouts.admin')

@section('title', 'Reports & Export')
@section('page_title', 'Reports & Export')
@section('breadcrumb')
  <li class="breadcrumb-item active" aria-current="page">Reports &amp; Export</li>
@endsection

@section('content')
@if ($errors->any())
  <div class="alert alert-danger"><ul class="mb-0">
    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
  </ul></div>
@endif

<div class="card mb-4" style="max-width: 900px">
  <div class="card-header"><h3 class="card-title">Generate a report</h3></div>
  <div class="card-body">
    <form method="GET" action="{{ route('exports.csv') }}">
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label" for="period">Period</label>
          <select id="period" name="period" class="form-select">
            <option value="daily" @selected(old('period') === 'daily')>Daily (today)</option>
            <option value="weekly" @selected(old('period') === 'weekly')>Weekly (this week)</option>
            <option value="monthly" @selected(old('period', 'monthly') === 'monthly')>Monthly (this month)</option>
            <option value="annual" @selected(old('period') === 'annual')>Annual (this year)</option>
            <option value="custom" @selected(old('period') === 'custom')>Custom range</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="from">From <small class="text-secondary">(custom only)</small></label>
          <input type="date" id="from" name="from" value="{{ old('from') }}" class="form-control">
        </div>
        <div class="col-md-4">
          <label class="form-label" for="to">To <small class="text-secondary">(custom only)</small></label>
          <input type="date" id="to" name="to" value="{{ old('to') }}" class="form-control">
        </div>

        <div class="col-md-4">
          <label class="form-label" for="status">Status</label>
          <select id="status" name="status" class="form-select">
            <option value="">Any status</option>
            <option value="open">Open cases (not resolved, closed or rejected)</option>
            @foreach (['submitted','under_review','assigned','investigating','resolved','closed','rejected'] as $s)
              <option value="{{ $s }}">{{ ucwords(str_replace('_', ' ', $s)) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="priority">Priority</label>
          <select id="priority" name="priority" class="form-select">
            <option value="">Any priority</option>
            @foreach (['low','medium','high','critical'] as $p)
              <option value="{{ $p }}">{{ ucfirst($p) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label" for="department_id">Department</label>
          <select id="department_id" name="department_id" class="form-select">
            <option value="">All departments</option>
            @foreach ($departments as $d)
              <option value="{{ $d->id }}">{{ $d->name }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 mt-4">
        <button type="submit" class="btn btn-success"><i class="bi bi-file-earmark-spreadsheet"></i> Download CSV (opens in Excel)</button>
        <button type="submit" formaction="{{ route('exports.print') }}" formtarget="_blank" class="btn btn-outline-primary">
          <i class="bi bi-printer"></i> Print / Save as PDF
        </button>
      </div>
      <p class="form-text mt-3 mb-0">
        Exports never include who submitted a report. Every export is recorded in the audit log.
      </p>
    </form>
  </div>
</div>
@endsection
