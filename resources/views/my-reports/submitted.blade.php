@extends('layouts.admin')

@section('title', 'Report submitted')

@section('page_title', 'Report submitted')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Report submitted</li>
@endsection

@section('content')
@push('styles')
    @vite(['resources/css/app.css'])
@endpush

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-success">
                <div class="card-body p-4 text-center">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem"></i>
                    <h1 class="h3 mt-2">Your report was submitted</h1>
                    <p class="text-secondary">Keep these two items. You need <strong>both</strong> to check progress.</p>

                    <div class="bg-body-tertiary rounded p-3 mb-3">
                        <div class="small text-secondary">Tracking number</div>
                        <div class="fs-4 fw-semibold code-box" id="tn">{{ $trackingNumber }}</div>
                    </div>
                    <div class="bg-body-tertiary rounded p-3 mb-3">
                        <div class="small text-secondary">Access code (secret)</div>
                        <div class="fs-4 fw-semibold code-box" id="ac">{{ substr($accessCode, 0, 5) }}-{{ substr($accessCode, 5) }}</div>
                    </div>

                    <div class="alert alert-warning text-start small">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>This is the only time the access code is shown.</strong> We cannot recover it, and this page cannot be reopened.
                        Write it down or save it somewhere private now.
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <button class="btn btn-outline-primary" id="copy"><i class="bi bi-clipboard"></i> Copy both</button>
                        <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
                        <a href="{{ route('my.reports.track.index') }}" class="btn btn-ju">Track my report</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('copy')?.addEventListener('click', async (e) => {
        const text = 'Tracking number: ' + document.getElementById('tn').textContent.trim()
                   + '\nAccess code: ' + document.getElementById('ac').textContent.trim();
        try { await navigator.clipboard.writeText(text); e.target.textContent = 'Copied'; }
        catch { alert('Copy failed. Please write the codes down.'); }
    });
</script>
@endpush
@endsection
