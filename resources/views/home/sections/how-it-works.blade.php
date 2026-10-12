<section class="jiacrs-section" id="how-it-works">
    <div class="container">

        <h2 class="jiacrs-section-title">How It Works</h2>
        <p class="jiacrs-section-subtitle">Reporting is simple, secure and confidential. Follow these steps.</p>

        <div class="row g-4">
            @foreach ([
                ['icon' => 'bi-pencil-square',  'title' => 'Submit a Report',          'text' => 'Provide details and supporting evidence.'],
                ['icon' => 'bi-person-vcard',   'title' => 'Receive Reference Number', 'text' => 'Get a unique tracking number and access key.'],
                ['icon' => 'bi-search',         'title' => 'Case Review',              'text' => 'Authorized officers review and process the report.'],
                ['icon' => 'bi-graph-up-arrow', 'title' => 'Track Progress',           'text' => 'Monitor the status securely using your reference number.'],
            ] as $step)
                <div class="col-sm-6 col-lg-3 jiacrs-step-col">
                    <div class="card jiacrs-step h-100 text-center shadow-sm">
                        <div class="card-body">
                            <div class="jiacrs-step-icon">
                                <i class="bi {{ $step['icon'] }}"></i>
                                <span class="jiacrs-step-badge">{{ $loop->iteration }}</span>
                            </div>
                            <h3 class="jiacrs-step-title">{{ $step['title'] }}</h3>
                            <p class="jiacrs-step-text">{{ $step['text'] }}</p>
                        </div>
                    </div>
                    @unless ($loop->last)
                        <i class="bi bi-arrow-right jiacrs-step-arrow d-none d-lg-block"></i>
                    @endunless
                </div>
            @endforeach
        </div>

        <div class="text-center mt-4">
            <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#reportTypeModal">
                <i class="bi bi-send-fill me-2"></i>Start a Report
            </button>
        </div>

    </div>
</section>