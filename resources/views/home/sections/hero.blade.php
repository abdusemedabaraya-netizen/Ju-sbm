<section class="jiacrs-hero"
         style="background-image: url('{{ asset('images/image.png') }}');">

    <div class="jiacrs-hero-overlay"></div>

    <div class="container">
        <div class="row">
            <div class="col-lg-7">

                <div class="jiacrs-hero-kicker">INTEGRITY BUILDS A STRONGER UNIVERSITY</div>

                <h1 class="jiacrs-hero-title">
                    Report Integrity Concerns
                    <span>Safely and Securely</span>
                </h1>

                <p class="jiacrs-hero-description">
                    JIACRS provides a secure and confidential platform for students, staff,
                    stakeholders, and the community to report corruption, misconduct, fraud,
                    abuse of resources, and other integrity-related concerns.
                </p>

                <div class="jiacrs-hero-features">
                    <div class="jiacrs-hero-feature">
                        <i class="bi bi-shield-check"></i>
                        <div><strong>Your identity</strong><small>is protected</small></div>
                    </div>
                    <div class="jiacrs-feature-divider"></div>
                    <div class="jiacrs-hero-feature">
                        <i class="bi bi-incognito"></i>
                        <div><strong>Anonymous reporting</strong><small>is supported</small></div>
                    </div>
                    <div class="jiacrs-feature-divider"></div>
                    <div class="jiacrs-hero-feature">
                        <i class="bi bi-shield-lock"></i>
                        <div><strong>Secure &amp; encrypted</strong><small>information</small></div>
                    </div>
                </div>

                <div class="jiacrs-hero-buttons">
    <a href="{{ route('report.create.anonymous') }}" class="btn btn-outline-light btn-lg">
    <i class="bi bi-incognito me-2"></i>Submit Anonymous Report
</a>
<a href="{{ route('report.create.identified') }}" class="btn btn-success btn-lg">
    <i class="bi bi-person-check me-2"></i>Submit Identified Report
</a>
</div>

            </div>
        </div>
    </div>

</section>