<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <div class="sidebar-brand">
    <a href="{{ route('dashboard') }}" class="brand-link">
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
          @hasanyrole('Super Admin|Admin|Investigator')
            <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->is('admin/dashboard') ? 'active' : '' }}">
          @else
            <a href="{{ route('reporter.dashboard') }}" class="nav-link {{ request()->is('my/dashboard') ? 'active' : '' }}">
          @endhasanyrole
            <i class="nav-icon bi bi-speedometer"></i><p>Dashboard</p>
          </a>
        </li>

        {{-- ===== Case handling ===== --}}
        @hasanyrole('Super Admin|Admin|Investigator')
          <li class="nav-header">CASE MANAGEMENT</li>

          <li class="nav-item {{ request()->is('admin/reports*') ? 'menu-open' : '' }}">
            <a href="#" class="nav-link {{ request()->is('admin/reports*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-file-earmark-text"></i>
              <p>Reports <i class="nav-arrow bi bi-chevron-right"></i></p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="{{ url('/admin/reports') }}" class="nav-link {{ request()->is('admin/reports') ? 'active' : '' }}">
                  <i class="nav-icon bi bi-circle"></i><p>All Reports</p>
                </a>
              </li>
              @hasanyrole('Super Admin|Admin')
                <li class="nav-item">
                  <a href="{{ url('/admin/reports?status=submitted') }}" class="nav-link">
                    <i class="nav-icon bi bi-circle"></i><p>New / Unassigned</p>
                  </a>
                </li>
              @endhasanyrole
              <li class="nav-item">
                <a href="{{ url('/admin/reports?priority=high') }}" class="nav-link">
                  <i class="nav-icon bi bi-circle"></i><p>High Risk</p>
                </a>
              </li>
            </ul>
          </li>

          @role('Investigator')
            <li class="nav-item">
             <a href="{{ route('investigations.mine') }}" class="nav-link {{ request()->is('admin/my-cases*') ? 'active' : '' }}">
                <i class="nav-icon bi bi-folder2-open"></i><p>My Assigned Cases</p>
              </a>
            </li>
          @endrole

          <li class="nav-item">
            <a href="{{ url('/admin/investigations') }}" class="nav-link {{ request()->is('admin/investigations*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-search"></i><p>Investigations</p>
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

        {{-- ===== Reporter (logged-in university staff) ===== --}}
        @role('User')
          <li class="nav-header">MY ACCOUNT</li>
          <li class="nav-item">
            <a href="{{ route('reporter.dashboard') }}" class="nav-link {{ request()->is('my/dashboard') ? 'active' : '' }}">
              <i class="nav-icon bi bi-speedometer"></i><p>Dashboard</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ route('report.create.identified') }}" class="nav-link">
              <i class="nav-icon bi bi-plus-circle"></i><p>Submit Report</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ route('my.reports.index') }}" class="nav-link {{ request()->is('my/reports*') ? 'active' : '' }}">
              <i class="nav-icon bi bi-file-earmark-text"></i><p>My Reports</p>
            </a>
          </li>
          <li class="nav-item">
            <!-- <a href="{{ route('profile.edit') }}" class="nav-link">
              <i class="nav-icon bi bi-person"></i><p>Profile</p>
            </a> -->
          </li>
        @endrole
      </ul>
    </nav>
  </div>
</aside>