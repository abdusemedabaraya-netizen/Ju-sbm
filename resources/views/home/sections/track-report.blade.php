<div class="card jiacrs-track card-primary card-outline shadow-sm" id="track">
    <div class="card-body">

        <h2 class="jiacrs-track-title"><i class="bi bi-search me-2"></i>Track Your Report</h2>
        <p class="jiacrs-track-text">Use your tracking number and access code to check the status of your report.</p>

        @if ($errors->has('tracking_number') || $errors->has('access_code'))
            <div class="alert alert-danger py-2 small" role="alert">
                {{ $errors->first('tracking_number') ?: $errors->first('access_code') }}
            </div>
        @endif

        <form method="POST" action="{{ route('track.search') }}" autocomplete="off">
            @csrf

            <div class="input-group mb-3">
                <span class="input-group-text"><i class="bi bi-person-vcard"></i></span>
                <input type="text" name="tracking_number" class="form-control"
                       placeholder="Tracking Number" aria-label="Tracking Number"
                       value="{{ old('tracking_number') }}" required maxlength="50">
            </div>

            <div class="input-group mb-3">
                <span class="input-group-text"><i class="bi bi-key"></i></span>
                <input type="password" name="access_code" id="accessCode" class="form-control"
                       placeholder="Access Code" aria-label="Access Code" required maxlength="100">
                <button class="btn btn-outline-secondary" type="button" id="toggleKey" aria-label="Show access code">
                    <i class="bi bi-eye"></i>
                </button>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-lg">
                <i class="bi bi-search me-2"></i>Track Report
            </button>
        </form>

        <p class="jiacrs-track-note"><i class="bi bi-info-circle me-1"></i>Keep your access code safe. It cannot be recovered if lost.</p>
    </div>
</div>

@push('scripts')
<script>
    document.getElementById('toggleKey')?.addEventListener('click', function () {
        const input = document.getElementById('accessCode');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        this.innerHTML = '<i class="bi bi-eye' + (show ? '-slash' : '') + '"></i>';
    });
</script>
@endpush