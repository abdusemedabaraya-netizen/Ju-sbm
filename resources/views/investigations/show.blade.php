@extends('layouts.admin')

@section('title', 'Investigation')
@section('page_title', 'Investigation')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('investigations.index') }}">Investigations</a></li>
  <li class="breadcrumb-item active" aria-current="page">{{ $investigation->report?->tracking_number }}</li>
@endsection

@php $isOwner = auth()->id() === $investigation->investigator_id; @endphp

@section('content')
@if ($errors->any())
  <div class="alert alert-danger"><ul class="mb-0">
    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
  </ul></div>
@endif

<div class="row">
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-header"><h3 class="card-title">Case</h3></div>
      <div class="card-body">
        <p class="mb-1"><code>{{ $investigation->report?->tracking_number }}</code></p>
        <p class="mb-2">{{ $investigation->report?->subject }}</p>
        <p class="mb-1">Status: @if($investigation->report)<x-status-badge :status="$investigation->report->status" />@endif</p>
        <p class="mb-3">Investigator: {{ $investigation->investigator?->name }}</p>
        <a href="{{ route('reports.show', $investigation->report_id) }}" class="btn btn-outline-primary btn-sm">Open report and evidence</a>
      </div>
    </div>

    @hasanyrole('Admin|Super Admin')
      <form method="POST" action="{{ route('investigations.destroy', $investigation) }}"
            onsubmit="return confirm('Remove this assignment?')">
        @csrf @method('DELETE')
        <button class="btn btn-outline-danger w-100">Remove assignment</button>
      </form>
    @endhasanyrole
  </div>

  <div class="col-lg-8">
    <div class="card mb-4">
      <div class="card-header"><h3 class="card-title">Findings &amp; recommendations</h3></div>
      <div class="card-body">
        @if ($isOwner)
          <form method="POST" action="{{ route('investigations.update', $investigation) }}">
            @csrf @method('PUT')
            <div class="mb-3">
              <label class="form-label" for="findings">Findings</label>
              <textarea id="findings" name="findings" rows="8" class="form-control @error('findings') is-invalid @enderror">{{ old('findings', $investigation->findings) }}</textarea>
              @error('findings')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
              <label class="form-label" for="recommendations">Recommendations</label>
              <textarea id="recommendations" name="recommendations" rows="5" class="form-control @error('recommendations') is-invalid @enderror">{{ old('recommendations', $investigation->recommendations) }}</textarea>
              @error('recommendations')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button class="btn btn-primary">Save</button>
          </form>
        @else
          <h6>Findings</h6>
          <div class="mb-3">{!! $investigation->findings ? nl2br(e($investigation->findings)) : '<span class="text-secondary">Nothing recorded yet.</span>' !!}</div>
          <h6>Recommendations</h6>
          <div class="mb-3">{!! $investigation->recommendations ? nl2br(e($investigation->recommendations)) : '<span class="text-secondary">Nothing recorded yet.</span>' !!}</div>
          <p class="text-secondary small mb-0">Only the assigned investigator can edit findings and recommendations.</p>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
