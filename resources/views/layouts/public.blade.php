<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light" data-lte-color-mode="off">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'JIACRS - Jimma University')</title>

    {{-- Font, Bootstrap Icons (the icon set AdminLTE 4 uses), AdminLTE 4 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.css') }}">

    {{-- JIACRS styles (loaded last so they win) --}}
    @vite(['resources/css/app.css'])

    @stack('styles')
</head>

<body class="jiacrs-body">

    {{-- ================= NAVBAR ================= --}}
    <nav class="navbar navbar-expand-lg navbar-dark jiacrs-navbar sticky-top">
        <div class="container">

            <a class="navbar-brand jiacrs-brand" href="{{ route('home') }}">
                <img src="{{ asset('images/jimma-logo.png') }}" alt="Jimma University logo" class="jiacrs-logo">
                <div class="jiacrs-brand-text">
                    <div class="jiacrs-university-name">JIMMA UNIVERSITY</div>
                    <div class="jiacrs-system-name">Integrity and Anti-Corruption Reporting System (JIACRS)</div>
                </div>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#publicNavbar" aria-controls="publicNavbar"
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="publicNavbar">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Home</a>
                    </li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#about">About JIACRS</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#how-it-works">How to Report</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#faq">FAQs</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#contact">Contact</a></li>
                    <li class="nav-item ms-lg-2">
        <a href="{{ route('login') }}" class="nav-link jiacrs-login-btn">
            Login
        </a>
    </li>

                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        <a href="{{ route('reports.track') }}" class="btn jiacrs-track-btn">
                            <i class="bi bi-search me-1"></i> Track Report
                        </a>
                    </li>
                </ul>
            </div>

        </div>
    </nav>

    {{-- ================= PAGE CONTENT ================= --}}
    <main>
        @yield('content')
    </main>

    {{-- ================= FOOTER ================= --}}
    <footer class="jiacrs-footer">
        <div class="container">
            <div class="row align-items-center gy-3">

                <div class="col-lg-4 d-flex align-items-center gap-3">
                    <img src="{{ asset('images/jimma-logo.png') }}" alt="" class="jiacrs-footer-logo">
                    <div>
                        <strong class="d-block">JIMMA UNIVERSITY</strong>
                        <small>Integrity and Anti-Corruption Reporting System (JIACRS)</small>
                    </div>
                </div>
 <div class="col-lg-5">
                    <ul class="list-inline jiacrs-footer-links mb-0">
                        <li class="list-inline-item"><a href="{{ route('home') }}">Home</a></li>
                        <li class="list-inline-item"><a href="{{ route('home') }}#about">About JIACRS</a></li>
                        <li class="list-inline-item"><a href="{{ route('home') }}#how-it-works">How to Report</a></li>
                        <li class="list-inline-item"><a href="{{ route('home') }}#faq">FAQs</a></li>
                        <li class="list-inline-item"><a href="{{ route('home') }}#contact">Contact</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 text-lg-end">
                    {{-- TODO: replace # with the official Jimma University social pages --}}
                    <a href="#" class="jiacrs-social" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="jiacrs-social" aria-label="X"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" class="jiacrs-social" aria-label="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="jiacrs-social" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                </div>

            </div>

            <hr class="jiacrs-footer-rule">

            <div class="d-flex flex-column flex-md-row justify-content-between gap-2 small">
                <span>&copy; {{ date('Y') }} Jimma University. All Rights Reserved.</span>
                <span>Secure Reporting &bull; Integrity &bull; Accountability &bull; Transparency</span>
            </div>
        </div>
    </footer>

    {{-- Bootstrap JS (needed for the mobile menu and the FAQ accordion) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')
</body>
</html>