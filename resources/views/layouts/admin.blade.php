<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="color-scheme" content="light dark">
  <title>@yield('title', 'Dashboard') | JIACRS</title>
  <link rel="icon" type="image/png" href="{{ asset('images/jimma-logo.png') }}">

  {{-- Prevents flash of wrong theme (from AdminLTE) --}}
  <script>
    (() => {
      const root = document.documentElement;
      let stored = null;
      try { stored = localStorage.getItem('lte-theme'); } catch {}
      let resolved = 'light';
      if (stored === 'dark' || stored === 'light') resolved = stored;
      else if (globalThis.matchMedia('(prefers-color-scheme: dark)').matches) resolved = 'dark';
      root.setAttribute('data-bs-theme', resolved);
      root.style.colorScheme = resolved;
      root.setAttribute('data-lte-theme-resolved', '');
    })();
  </script>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fontsource/source-sans-3@5.0.12/index.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/styles/overlayscrollbars.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="{{ asset('adminlte/css/adminlte.css') }}">
  @stack('styles')
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
@auth
<script>
  // Shared global state for the signed-in user (single source mirrored from users.avatar).
  window.JIACRS = window.JIACRS || {};
  window.JIACRS.user = {
    id: {{ auth()->id() }},
    name: @json(auth()->user()->name),
    avatarUrl: @json(auth()->user()->avatar_url),
  };
</script>
@endauth
<div class="app-wrapper">

  @include('layouts.partials.navbar')
  @include('layouts.partials.sidebar')

  <main class="app-main">
    <div class="app-content-header">
      <div class="container-fluid">
        <div class="row">
          <div class="col-sm-6">
            <h1 class="mb-0 fs-3">@yield('page_title', 'Dashboard')</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-end">
              <li class="breadcrumb-item"><a href="{{ auth()->user()?->isStaff() ? route('admin.dashboard') : route('dashboard') }}">Home</a></li>
              @yield('breadcrumb')
            </ol>
          </div>
        </div>
      </div>
    </div>

    <div class="app-content">
      <div class="container-fluid">
        @if (session('success'))
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif
        @if (session('error'))
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        @endif

        @yield('content')
      </div>
    </div>
  </main>

  @include('layouts.partials.footer')
</div>

<script src="https://cdn.jsdelivr.net/npm/overlayscrollbars@2.11.0/browser/overlayscrollbars.browser.es6.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.min.js"></script>
<script src="{{ asset('adminlte/js/adminlte.js') }}"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const sidebarWrapper = document.querySelector('.sidebar-wrapper');
    const isMobile = window.innerWidth <= 992;
    if (sidebarWrapper && OverlayScrollbarsGlobal?.OverlayScrollbars !== undefined && !isMobile) {
      OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
        scrollbars: { theme: 'os-theme-light', autoHide: 'leave', clickScroll: true },
      });
    }
  });
</script>
@stack('scripts')
</body>
</html>