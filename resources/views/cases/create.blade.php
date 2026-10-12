@extends('layouts.admin')

@section('title', 'Assign Investigator')
@section('page_title', 'Assign Investigator')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('investigations.index') }}">Investigations</a></li>
  <li class="breadcrumb-item active" aria-current="page">New</li>
@endsection

@section('content')
  <div class="card mb-4">
    <div class="card-header">
      <h3 class="card-title">Assign an Investigator to a Report</h3>
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

      <form action="{{ route('investigations.store') }}" method="POST">
        @csrf

        <div class="mb-3">
          <label for="report_id" class="form-label">Report <span class="text-danger">*</span></label>
          <select name="report_id" id="report_id" class="form-select @error('report_id') is-invalid @enderror" required>
            <option value="">— Select a report —</option>
            @foreach ($reports as $report)
              <option value="{{ $report->id }}" @selected(old('report_id') == $report->id)>
                {{ $report->tracking_number }} — {{ $report->subject }}
              </option>
            @endforeach
          </select>
          @error('report_id')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="mb-3">
          <label for="investigator_id" class="form-label">Investigator <span class="text-danger">*</span></label>
          <select name="investigator_id" id="investigator_id" class="form-select @error('investigator_id') is-invalid @enderror" required>
            <option value="">— Select an investigator —</option>
            @foreach ($investigators as $investigator)
              <option value="{{ $investigator->id }}" @selected(old('investigator_id') == $investigator->id)>
                {{ $investigator->name }}
              </option>
            @endforeach
          </select>
          @error('investigator_id')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-person-check"></i> Assign
          </button>
          <a href="{{ route('investigations.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
      </form>
    </div>
  </div>
@endsection
