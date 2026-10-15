@extends('layouts.admin')

@section('title', 'Report status')

@section('page_title', 'Report status')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Report status</li>
@endsection

@section('content')
@push('styles')
    @vite(['resources/css/app.css'])
@endpush

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <p class="text-secondary mb-1">Tracking number</p>
                    <h1 class="h4 code-box">{{ $report['tracking_number'] }}</h1>

                    <p class="mt-3 mb-1">Type: {{ ucwords(str_replace('_', ' ', $report['category'])) }}</p>
                    <p class="mb-3">Current status: <x-status-badge :status="$report['status']" /></p>

                    <h2 class="h6">Progress</h2>
                    <ul class="list-unstyled">
                        @foreach ($history as $h)
                            <li class="mb-2">
                                <x-status-badge :status="$h->to_status" />
                                <small class="text-secondary ms-2">{{ $h->created_at->format('d M Y') }}</small>
                            </li>
                        @endforeach
                    </ul>

                    <p class="small text-secondary mb-3">
                        For your protection, investigation details and staff names are never shown here.
                    </p>
                    <a href="{{ route('my.reports.track.index') }}" class="btn btn-outline-secondary">Check another report</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
