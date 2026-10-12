
<nav class="app-header navbar navbar-expand bg-white border-bottom">
    <div class="container-fluid">

        {{-- Left Side --}}
        <ul class="navbar-nav">

            {{-- Sidebar Toggle --}}
            <li class="nav-item">
                <a class="nav-link"
                   data-lte-toggle="sidebar"
                   href="#"
                   role="button">
                    <i class="bi bi-list"></i>
                </a>
            </li>

            {{-- Page/System Name --}}
            <li class="nav-item d-none d-md-block">
                <span class="nav-link fw-semibold text-dark">
                    JIACRS
                </span>
            </li>

        </ul>

        {{-- Right Side --}}
        <ul class="navbar-nav ms-auto">

            {{-- User Dropdown --}}
            <li class="nav-item dropdown">

                <a class="nav-link dropdown-toggle"
                   href="#"
                   data-bs-toggle="dropdown">

                    <span class="me-2">
                        {{ Auth::user()->name }}
                    </span>

                    <i class="bi bi-person-circle"></i>

                </a>

                <ul class="dropdown-menu dropdown-menu-end">

                    {{-- User Information --}}
                    <li class="dropdown-header text-center">

                        <div class="jiacrs-navbar-avatar">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>

                        <strong>
                            {{ Auth::user()->name }}
                        </strong>

                        <br>

                        <small class="text-muted">
                            {{ Auth::user()->email }}
                        </small>

                    </li>

                    <li>
                        <hr class="dropdown-divider">
                    </li>

                    {{-- Profile --}}
                    <li>
                        <a class="dropdown-item"
                           href="{{ route('profile.edit') }}">
                            <i class="bi bi-person me-2"></i>
                            Profile
                        </a>
                    </li>

                    {{-- Logout --}}
                    <li>
                        <form method="POST"
                              action="{{ route('logout') }}">
                            @csrf

                            <button type="submit"
                                    class="dropdown-item">
                                <i class="bi bi-box-arrow-right me-2"></i>
                                Log Out
                            </button>
                        </form>
                    </li>

                </ul>

            </li>

        </ul>

    </div>
</nav>
