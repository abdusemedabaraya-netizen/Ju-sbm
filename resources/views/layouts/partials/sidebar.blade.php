@php
  $isStaff = auth()->user()?->hasAnyRole(['Super Admin', 'Admin', 'Investigator']) ?? false;
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

        {{-- ===== Case handling ===== --}}
        @hasanyrole('Super Admin|Admin|Investigator')
          @php
            $q = request();
            $onReportsList = $q->routeIs('reports.index');
            $onReports = $onReportsList || $q->routeIs('reports.show');
            $view = $q->query('view');
            $status = $q->query('status');
            $priority = $q->query('priority');
            $hasExtraFilters = $q->filled('search') || $q->filled('department_id')
              || $q->filled('category') || $q->filled('date_from') || $q->filled('date_to');
            $activeNew         = $onReportsList && ($view === 'new' || $status === 'submitted');
            $activeUnderReview = $onReportsList && ($view === 'under_review' || $status === 'under_review');
            $activeInProgress  = $onReportsList && ($view === 'in_progress' || in_array($status, ['assigned', 'investigating'], true));
            $activeHighRisk    = $onReportsList && ($view === 'high_risk' || $priority === 'high');
            $activeResolved    = $onReportsList && ($view === 'resolved_closed' || in_array($status, ['resolved', 'closed'], true));
            $activeRejected    = $onReportsList && ($view === 'rejected' || $status === 'rejected');
            $activeAll         = $onReportsList && ! $view && ! $status && ! $priority && ! $hasExtraFilters;
          @endphp

          <li class="nav-header">REPORTS</li>

          {{-- Parent opens automatically when a child (index or show) is active. --}}
          <li class="nav-item {{ $onReports ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ $onReports ? 'active' : '' }}">
              <i class="nav-icon bi bi-file-earmark-text"></i>
              <p>Reports <i class="nav-arrow bi bi-chevron-right"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ route('reports.index') }}" class="nav-link {{ $activeAll ? 'active' : '' }}">
                  <i class="nav-icon bi bi-circle"></i>
                  <p>All Reports <span class="badge text-bg-secondary ms-1">{{ $reportCounts['all'] ?? 0 }}</span></p>
                </a>
              </li>

              @hasanyrole('Super Admin|Admin')
                <li class="nav-item">
                  <a href="{{ route('reports.index', ['view' => 'new']) }}" class="nav-link {{ $activeNew ? 'active' : '' }}">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>New <span class="badge text-bg-warning ms-1">{{ $reportCounts['new'] ?? 0 }}</span></p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="{{ route('reports.index', ['view' => 'under_review']) }}" class="nav-link {{ $activeUnderReview ? 'active' : '' }}">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Under Review <span class="badge text-bg-info ms-1">{{ $reportCounts['under_review'] ?? 0 }}</span></p>
                  </a>
                </li>
              @endhasanyrole

              <li class="nav-item">
                <a href="{{ route('reports.index', ['view' => 'in_progress']) }}" class="nav-link {{ $activeInProgress ? 'active' : '' }}">
                  <i class="nav-icon bi bi-circle"></i>
                  <p>In Progress <span class="badge text-bg-primary ms-1">{{ $reportCounts['in_progress'] ?? 0 }}</span></p>
                </a>
              </li>

              @hasanyrole('Super Admin|Admin')
                <li class="nav-item">
                  <a href="{{ route('reports.index', ['view' => 'high_risk']) }}" class="nav-link {{ $activeHighRisk ? 'active' : '' }}">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>High Risk <span class="badge text-bg-danger ms-1">{{ $reportCounts['high_risk'] ?? 0 }}</span></p>
                  </a>
                </li>
              @endhasanyrole

              <li class="nav-item">
                <a href="{{ route('reports.index', ['view' => 'resolved_closed']) }}" class="nav-link {{ $activeResolved ? 'active' : '' }}">
                  <i class="nav-icon bi bi-circle"></i>
                  <p>Resolved &amp; Closed <span class="badge text-bg-success ms-1">{{ $reportCounts['resolved_closed'] ?? 0 }}</span></p>
                </a>
              </li>

              @hasanyrole('Super Admin|Admin')
                <li class="nav-item">
                  <a href="{{ route('reports.index', ['view' => 'rejected']) }}" class="nav-link {{ $activeRejected ? 'active' : '' }}">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>Rejected <span class="badge text-bg-danger ms-1">{{ $reportCounts['rejected'] ?? 0 }}</span></p>
                  </a>
                </li>
              @endhasanyrole
            </ul>
          </li>

          @php
            $onCases = request()->routeIs('investigations.index')
              || request()->routeIs('investigations.show')
              || request()->routeIs('investigations.create');
            $caseTab = request()->query('tab', 'active');
            $onCaseActive = request()->routeIs('investigations.index') && $caseTab !== 'completed';
            $onCaseCompleted = request()->routeIs('investigations.index') && $caseTab === 'completed';
          @endphp

          <li class="nav-item {{ $onCases ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ $onCases ? 'active' : '' }}">
              <i class="nav-icon bi bi-search"></i>
              <p>Investigations <i class="nav-arrow bi bi-chevron-right"></i></p>
            </a>
            <ul class="nav nav-treeview">
              @hasanyrole('Super Admin|Admin')
                <li class="nav-item">
                  <a href="{{ route('investigations.index') }}" class="nav-link {{ $onCaseActive ? 'active' : '' }}">
                    <i class="nav-icon bi bi-circle"></i>
                    <p>All Investigations <span class="badge text-bg-secondary ms-1">{{ $caseCounts['total'] ?? 0 }}</span></p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="{{ route('investigations.create') }}" class="nav-link {{ request()->routeIs('investigations.create') ? 'active' : '' }}">
                    <i class="nav-icon bi bi-person-plus"></i>
                    <p>Assign Investigator <span class="badge text-bg-warning ms-1">{{ $caseCounts['unassigned'] ?? 0 }}</span></p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="{{ route('investigations.index', ['tab' => 'completed']) }}" class="nav-link {{ $onCaseCompleted ? 'active' : '' }}">
                    <i class="nav-icon bi bi-check2-circle"></i>
                    <p>Completed <span class="badge text-bg-success ms-1">{{ $caseCounts['completed'] ?? 0 }}</span></p>
                  </a>
                </li>
              @endhasanyrole

              @role('Investigator')
                <li class="nav-item">
                  <a href="{{ route('investigations.index') }}" class="nav-link {{ $onCaseActive ? 'active' : '' }}">
                    <i class="nav-icon bi bi-folder2-open"></i>
                    <p>My Assigned Cases <span class="badge text-bg-primary ms-1">{{ $caseCounts['active'] ?? 0 }}</span></p>
                  </a>
                </li>
                <li class="nav-item">
                  <a href="{{ route('investigations.index', ['tab' => 'completed']) }}" class="nav-link {{ $onCaseCompleted ? 'active' : '' }}">
                    <i class="nav-icon bi bi-check2-circle"></i>
                    <p>Completed <span class="badge text-bg-success ms-1">{{ $caseCounts['completed'] ?? 0 }}</span></p>
                  </a>
                </li>
              @endrole
            </ul>
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
            <a href="{{ route('report.create') }}" class="nav-link {{ request()->routeIs('report.create') ? 'active' : '' }}">
              <i class="nav-icon bi bi-plus-circle"></i><p>Submit Report</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ url('/my/reports') }}" class="nav-link {{ request()->is('my/reports') ? 'active' : '' }}">
              <i class="nav-icon bi bi-file-earmark-text"></i><p>My Reports</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ route('profile.edit') }}" class="nav-link">
              <i class="nav-icon bi bi-person"></i><p>Profile</p>
            </a>
          </li>
        @endrole
      </ul>
    </nav>
  </div>
</aside>