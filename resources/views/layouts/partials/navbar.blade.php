@php
  $user = auth()->user();
  $unread = $user->unreadNotifications()->latest()->take(5)->get();
  $unreadCount = $user->unreadNotifications()->count();
@endphp
<nav class="app-header navbar navbar-expand bg-body">
  <div class="container-fluid">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" aria-label="Toggle sidebar">
          <i class="bi bi-list"></i>
        </a>
      </li>
      <!-- <li class="nav-item d-none d-md-block">
        <a href="{{ url('/') }}" class="nav-link" target="_blank">
          <i class="bi bi-globe me-1" aria-hidden="true"></i> Public Portal
        </a>
      </li> -->
    </ul>

    <ul class="navbar-nav ms-auto">
      {{-- Notifications (Laravel database notifications) --}}
      <li class="nav-item dropdown">
        <a class="nav-link" data-bs-toggle="dropdown" href="#" aria-label="Notifications: {{ $unreadCount }} unread">
          <i class="bi bi-bell-fill"></i>
          @if ($unreadCount)
            <span class="navbar-badge badge text-bg-warning">{{ $unreadCount }}</span>
          @endif
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
          <span class="dropdown-item dropdown-header">{{ $unreadCount }} Notifications</span>
          @forelse ($unread as $n)
            <div class="dropdown-divider"></div>
            <a href="{{ $n->data['url'] ?? '#' }}" class="dropdown-item">
              <i class="bi bi-envelope me-2"></i> {{ \Illuminate\Support\Str::limit($n->data['title'] ?? 'Notification', 28) }}
              <span class="float-end text-secondary fs-7">{{ $n->created_at->diffForHumans(null, true) }}</span>
            </a>
          @empty
            <div class="dropdown-divider"></div>
            <span class="dropdown-item text-secondary">No new notifications</span>
          @endforelse
          <div class="dropdown-divider"></div>
         <a href="{{ route('notifications.index') }}" class="dropdown-item dropdown-footer">See All Notifications</a>
        </div>
      </li>

      {{-- Fullscreen --}}
      <li class="nav-item">
        <a class="nav-link" href="#" data-lte-toggle="fullscreen" aria-label="Toggle fullscreen">
          <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
          <i data-lte-icon="minimize" class="bi bi-fullscreen-exit d-none"></i>
        </a>
      </li>

      {{-- Light / Dark / Auto --}}
      <li class="nav-item dropdown">
        <a class="nav-link" href="#" id="bd-theme" aria-label="Toggle color scheme" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-sun-fill" data-lte-theme-icon="light"></i>
          <i class="bi bi-moon-fill d-none" data-lte-theme-icon="dark"></i>
          <i class="bi bi-circle-half d-none" data-lte-theme-icon="auto"></i>
        </a>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme" style="--bs-dropdown-min-width: 8rem">
          <li><button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light" aria-pressed="false">
            <i class="bi bi-sun-fill me-2"></i> Light <i class="bi bi-check-lg ms-auto d-none"></i></button></li>
          <li><button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark" aria-pressed="false">
            <i class="bi bi-moon-fill me-2"></i> Dark <i class="bi bi-check-lg ms-auto d-none"></i></button></li>
          <li><button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="auto" aria-pressed="true">
            <i class="bi bi-circle-half me-2"></i> Auto <i class="bi bi-check-lg ms-auto d-none"></i></button></li>
        </ul>
      </li>

      {{-- User menu --}}
      <li class="nav-item dropdown user-menu">
        <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle fs-5 me-1"></i>
          <span class="d-none d-md-inline">{{ $user->name }}</span>
        </a>
        <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
          <li class="user-header text-bg-primary">
            <i class="bi bi-person-circle" style="font-size: 4rem"></i>
            <p>
              {{ $user->name }}
              <small>{{ $user->getRoleNames()->first() ?? 'User' }}</small>
              <small>Member since {{ $user->created_at->format('M Y') }}</small>
            </p>
          </li>
          <li class="user-footer">
            <a href="{{ route('profile.edit') }}" class="btn btn-outline-secondary">Profile</a>
            <form method="POST" action="{{ route('logout') }}" class="d-inline float-end">
              @csrf
              <button type="submit" class="btn btn-outline-danger">Sign out</button>
            </form>
          </li>
        </ul>
      </li>
    </ul>
  </div>
</nav>