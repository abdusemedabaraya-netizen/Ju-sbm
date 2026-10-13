@extends('layouts.public')

@section('title', 'Track a report')

@section('content')
<div class="container py-5" style="max-width: 560px">
    <h1 class="h3 mb-3">Track your report</h1>

    <div class="card shadow-sm jiacrs-track">
        <div class="card-body p-4">
            <p class="jiacrs-track-text">Use your tracking number and access code to check the status of your report.</p>

            @if ($errors->has('tracking_number') || $errors->has('access_code'))
                <div class="alert alert-danger py-2 small" role="alert">
                    {{ $errors->first('tracking_number') ?: $errors->first('access_code') }}
                </div>
            @endif

            <form method="POST" action="{{ route('track.search') }}" class="jiacrs-track-form">
                @csrf

                <div class="jiacrs-track-form-group">
                    <label class="jiacrs-track-label" for="tracking_number">Tracking number</label>
                    <div class="input-group jiacrs-track-input-group">
                        <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                        <input type="text" id="tracking_number" name="tracking_number"
                               value="{{ old('tracking_number') }}"
                               placeholder="JU-2026-000001"
                               class="form-control @error('tracking_number') is-invalid @enderror" required maxlength="50">
                    </div>
                    @error('tracking_number') <div class="jiacrs-track-error">{{ $message }}</div> @enderror
                </div>

                <div class="jiacrs-track-form-group">
                    <label class="jiacrs-track-label" for="access_code">Access code</label>
                    <div class="input-group jiacrs-track-input-group">
                        <span class="input-group-text"><i class="bi bi-key"></i></span>
                        <input type="password" id="access_code" name="access_code"
                               value="{{ old('access_code') }}"
                               placeholder="XXXXX-XXXXX" autocomplete="off"
                               class="form-control @error('access_code') is-invalid @enderror" required maxlength="50">
                        <button class="btn btn-outline-secondary" type="button" id="toggleAccessCode" aria-label="Show access code">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    @error('access_code') <div class="jiacrs-track-error">{{ $message }}</div> @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-lg jiacrs-track-submit">
                    <i class="bi bi-search me-2"></i>Track Report
                </button>

                <p class="jiacrs-track-note"><i class="bi bi-info-circle me-1"></i>Keep your access code safe. It cannot be recovered if lost.</p>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('toggleAccessCode')?.addEventListener('click', function () {
        const input = document.getElementById('access_code');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.innerHTML = '<i class="bi bi-eye' + (show ? '-slash' : '') + '"></i>';
    });
</script>
@endpush
@endsection