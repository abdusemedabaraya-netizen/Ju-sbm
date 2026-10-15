@php
  $isStaff = auth()->user()?->isStaff() ?? false;
  $dashboardUrl = $isStaff ? route('admin.dashboard') : route('dashboard');
  $dashboardRouteName = $isStaff ? 'admin.dashboard' : 'dashboard';
@endphp
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <div class="sidebar-brand">
    <a href="{{ $dashboardUrl }}" class="brand-link">
      {{-- Put your logo at public/images/ju-logo.png (optional) --}}
      {{-- <img src="{{ asset('images/ju-logo.png') }}" alt="JU Logo" class="brand-image opacity-75 shadow"> --}}
      <i class="bi bi-shield-check brand-image fs-3 text-white"></i>
      <span class="brand-text fw-light">JIACRS</span>
    </a>
  </div>

  <div class="sidebar-wrapper">
    <nav class="mt-2" aria-label="Main navigation">
      <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" data-accordion="false" id="navigation">

        <li class="nav-item">
          <a href="{{ $dashboardUrl }}" class="nav-link {{ request()->routeIs($dashboardRouteName) ? 'active' : '' }}">
            <i class="nav-icon bi bi-speedometer"></i><p>Dashboard</p>
          </a>
        </li>

        {{-- ===== Case handling =====
             One entry per area. Report status presets and case tabs live on the
             pages themselves, so they are not duplicated here. --}}
        @hasanyrole('Super Admin|Admin|Investigator')
          <li class="nav-header">CASE HANDLING</li>

          <li class="nav-item">
            <a href="{{ route('reports.index') }}"
               class="nav-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-file-earmark-text"></i>
              <p>Reports <span class="badge text-bg-secondary ms-1">{{ $reportCounts['all'] ?? 0 }}</span></p>
            </a>
          </li>

          <li class="nav-item">
            <a href="{{ route('investigations.index') }}"
               class="nav-link {{ request()->routeIs('investigations.*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-search"></i>
              <p>Investigations <span class="badge text-bg-secondary ms-1">{{ $caseCounts['total'] ?? 0 }}</span></p>
            </a>
          </li>
        @endhasanyrole

        {{-- ===== Analytics ===== --}}
        @hasanyrole('Super Admin|Admin')
          <li class="nav-header">ANALYTICS</li>
          <li class="nav-item">
            <a href="{{ url('/admin/analytics') }}" class="nav-link {{ request()->is('admin/analytics*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-graph-up"></i><p>Analytics</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ url('/admin/exports') }}" class="nav-link {{ request()->is('admin/exports*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-file-earmark-arrow-down"></i><p>Reports &amp; Export</p>
            </a>
          </li>

          <li class="nav-header">ADMINISTRATION</li>
          <li class="nav-item">
            <a href="{{ url('/admin/departments') }}" class="nav-link {{ request()->is('admin/departments*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-building"></i><p>Departments</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ url('/admin/categories') }}" class="nav-link {{ request()->is('admin/categories*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-tags"></i><p>Categories</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ url('/admin/users') }}" class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-people"></i><p>Users</p>
            </a>
          </li>
        @endhasanyrole

        @role('Super Admin')
          <li class="nav-item">
            <a href="{{ url('/admin/roles') }}" class="nav-link {{ request()->is('admin/roles*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-person-lock"></i><p>Roles &amp; Permissions</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ url('/admin/audit-logs') }}" class="nav-link {{ request()->is('admin/audit-logs*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-clipboard-data"></i><p>Audit Log</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ url('/admin/settings') }}" class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-gear"></i><p>Settings</p>
            </a>
          </li>
        @endrole

        {{-- ===== Registered users (reporters) ===== --}}
        @role('User')
          <li class="nav-header">MY ACCOUNT</li>
          <li class="nav-item">
            <a href="{{ route('my.reports.create') }}" class="nav-link {{ request()->routeIs('my.reports.create') ? 'active' : '' }}">
              <i class="nav-icon bi bi-plus-circle"></i><p>Submit Report</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ url('/my/reports') }}" class="nav-link {{ request()->is('my/reports') ? 'active' : '' }}">
              <i class="nav-icon bi bi-file-earmark-text"></i><p>My Reports</p>
            </a>
          </li>
        @endrole
      </ul>
    </nav>
  </div>
</aside>
