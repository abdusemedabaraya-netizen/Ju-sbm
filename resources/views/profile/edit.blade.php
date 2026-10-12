@extends('layouts.admin')

@section('title', 'My profile')
@section('page_title', 'My profile')
@section('breadcrumb')
  <li class="breadcrumb-item active" aria-current="page">Profile</li>
@endsection

@section('content')
@if (session('status') === 'password-updated')
  <div class="alert alert-success">Password updated.</div>
@endif

<div class="row">
  {{-- ============ Left: photo and summary ============ --}}
  <div class="col-lg-4">
    <div class="card mb-4">
      <div class="card-body text-center">
        <x-avatar :user="$user" :size="130" />
        <h4 class="mt-3 mb-0">{{ $user->name }}</h4>
        <div class="text-secondary">{{ $user->job_title ?: 'No job title set' }}</div>
        <div class="mt-2">
          @forelse ($user->roles as $role)
            <span class="badge text-bg-primary">{{ $role->name }}</span>
          @empty
            <span class="badge text-bg-secondary">Member</span>
          @endforelse
        </div>

        <hr>
        <ul class="list-unstyled text-start small mb-3">
          <li class="mb-1"><i class="bi bi-envelope me-2 text-secondary"></i>{{ $user->email }}</li>
          <li class="mb-1"><i class="bi bi-telephone me-2 text-secondary"></i>{{ $user->phone ?: '—' }}</li>
          <li class="mb-1"><i class="bi bi-building me-2 text-secondary"></i>{{ $user->department?->name ?? '—' }}</li>
          <li><i class="bi bi-calendar3 me-2 text-secondary"></i>Member since {{ $user->created_at->format('M Y') }}</li>
        </ul>

        <form id="avatar-upload-form" method="POST" action="{{ route('profile.photo.store') }}" enctype="multipart/form-data" class="text-start">
          @csrf
          <label class="form-label small" for="photo">Profile photo</label>
          <input type="file" id="photo" name="photo" accept=".jpg,.jpeg,.png"
                 class="form-control form-control-sm @error('photo') is-invalid @enderror" required>
          @error('photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
          <div class="form-text">JPG or PNG, up to 2 MB. It is cropped to a square and its location data is removed.</div>
          <div id="avatar-upload-error" class="text-danger small mt-1 d-none"></div>
          <button type="submit" class="btn btn-primary btn-sm w-100 mt-2"><i class="bi bi-upload"></i> Upload photo</button>
        </form>

        <form id="avatar-remove-form" method="POST" action="{{ route('profile.photo.destroy') }}" class="mt-2 @unless ($user->avatar) d-none @endunless">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-outline-danger btn-sm w-100">Remove photo</button>
        </form>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><h3 class="card-title">Recent sign-ins</h3></div>
      <ul class="list-group list-group-flush small">
        @forelse ($recentLogins as $login)
          <li class="list-group-item d-flex justify-content-between">
            <span>{{ $login->created_at->format('d M Y H:i') }}</span>
            <span class="text-secondary">{{ $login->ip_address ?? '—' }}</span>
          </li>
        @empty
          <li class="list-group-item text-secondary">No sign-ins recorded yet.</li>
        @endforelse
      </ul>
      <div class="card-footer text-secondary small">If you do not recognise one, change your password now.</div>
    </div>
  </div>

  {{-- ============ Right: forms ============ --}}
  <div class="col-lg-8">
    <div class="card mb-4">
      <div class="card-header"><h3 class="card-title">Personal information</h3></div>
      <div class="card-body">
        <form method="POST" action="{{ route('profile.update') }}">
          @csrf @method('PATCH')
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="name">Full name</label>
              <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                     class="form-control @error('name') is-invalid @enderror" required maxlength="255">
              @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label class="form-label" for="email">Email</label>
              <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                     class="form-control @error('email') is-invalid @enderror"
                     @if ($isStaff) readonly @else required @endif>
              @if ($isStaff)
                <div class="form-text">Staff e-mail is changed by the Super Admin.</div>
              @elseif (! $user->hasVerifiedEmail())
                <div class="form-text text-warning">Your e-mail is not verified.</div>
              @endif
              @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label class="form-label" for="phone">Phone</label>
              <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                     class="form-control @error('phone') is-invalid @enderror" maxlength="30" placeholder="+251 …">
              @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
              <label class="form-label" for="job_title">Job title</label>
              <input type="text" id="job_title" name="job_title" value="{{ old('job_title', $user->job_title) }}"
                     class="form-control @error('job_title') is-invalid @enderror" maxlength="100">
              @error('job_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
              <label class="form-label" for="department_id">Department</label>
              <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror">
                <option value="">— none —</option>
                @foreach ($departments as $d)
                  <option value="{{ $d->id }}" @selected(old('department_id', $user->department_id) == $d->id)>{{ $d->name }}</option>
                @endforeach
              </select>
              @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-12">
              <label class="form-label" for="bio">About me</label>
              <textarea id="bio" name="bio" rows="3" maxlength="500"
                        class="form-control @error('bio') is-invalid @enderror">{{ old('bio', $user->bio) }}</textarea>
              <div class="form-text">Up to 500 characters.</div>
              @error('bio')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
          </div>
          <button class="btn btn-primary mt-3">Save changes</button>
        </form>
      </div>
    </div>

    <div class="card mb-4">
      <div class="card-header"><h3 class="card-title">Change password</h3></div>
      <div class="card-body">
        <form method="POST" action="{{ route('password.update') }}">
          @csrf @method('PUT')
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label" for="current_password">Current password</label>
              <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                     class="form-control @if ($errors->updatePassword->has('current_password')) is-invalid @endif" required>
              @if ($errors->updatePassword->has('current_password'))
                <div class="invalid-feedback">{{ $errors->updatePassword->first('current_password') }}</div>
              @endif
            </div>
            <div class="col-md-6">
              <label class="form-label" for="password">New password</label>
              <input type="password" id="password" name="password" autocomplete="new-password"
                     class="form-control @if ($errors->updatePassword->has('password')) is-invalid @endif" required>
              @if ($errors->updatePassword->has('password'))
                <div class="invalid-feedback">{{ $errors->updatePassword->first('password') }}</div>
              @endif
            </div>
            <div class="col-md-6">
              <label class="form-label" for="password_confirmation">Confirm new password</label>
              <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                     class="form-control" required>
            </div>
          </div>
          <button class="btn btn-primary mt-3">Update password</button>
        </form>
      </div>
    </div>

    @unless ($isStaff)
      <div class="card border-danger mb-4">
        <div class="card-header text-danger"><h3 class="card-title">Delete account</h3></div>
        <div class="card-body">
          <p class="text-secondary">
            This removes your account. Reports you submitted stay in the system but are no longer linked to you.
          </p>
          <form method="POST" action="{{ route('profile.destroy') }}"
                onsubmit="return confirm('Delete your account permanently?')" class="row g-2">
            @csrf @method('DELETE')
            <div class="col-md-8">
              <input type="password" name="password" placeholder="Confirm with your password"
                     class="form-control @if ($errors->userDeletion->has('password')) is-invalid @endif" required>
              @if ($errors->userDeletion->has('password'))
                <div class="invalid-feedback">{{ $errors->userDeletion->first('password') }}</div>
              @endif
            </div>
            <div class="col-md-4"><button class="btn btn-danger w-100">Delete account</button></div>
          </form>
        </div>
      </div>
    @endunless
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]');
  const uploadForm = document.getElementById('avatar-upload-form');
  const removeForm = document.getElementById('avatar-remove-form');
  const errorBox = document.getElementById('avatar-upload-error');

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function avatarMarkup(size, ring, url, name) {
    const ringCss = ring ? 'border:3px solid rgba(255, 255, 255, .65);' : '';
    if (url) {
      return '<img src="' + escapeHtml(url) + '" alt="' + escapeHtml(name) + '" class="rounded-circle object-fit-cover" ' +
        'style="width:' + size + 'px;height:' + size + 'px;object-fit:cover;border-radius:50%;' + ringCss + '">';
    }
    return '<i class="bi bi-person-circle text-secondary" aria-label="' + escapeHtml(name) + '" ' +
      'style="width:' + size + 'px;height:' + size + 'px;border-radius:50%;font-size:' + Math.round(size * 0.9) + 'px;line-height:1;' +
      ringCss + 'display:inline-flex;align-items:center;justify-content:center"></i>';
  }

  // Rewrites every avatar on the page (navbar, dropdown, profile) from the shared state.
  function applyAvatar(url, name) {
    document.querySelectorAll('[data-avatar-wrap]').forEach(function (wrap) {
      const size = parseInt(wrap.getAttribute('data-avatar-size') || '40', 10);
      const ring = wrap.getAttribute('data-avatar-ring') === '1';
      wrap.innerHTML = avatarMarkup(size, ring, url, name);
      wrap.setAttribute('data-avatar-name', name);
    });
    if (window.JIACRS) {
      window.JIACRS.user = Object.assign({}, window.JIACRS.user, { name: name, avatarUrl: url });
    }
    if (removeForm) {
      removeForm.classList.toggle('d-none', !url);
    }
  }

  function showError(message) {
    if (!errorBox) { return; }
    errorBox.textContent = message || '';
    errorBox.classList.toggle('d-none', !message);
  }

  // Always POST with FormData so Laravel's method spoofing (_method) works for DELETE too.
  function submit(form) {
    return fetch(form.action, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrf ? csrf.content : '',
        'Accept': 'application/json',
      },
      body: new FormData(form),
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (payload) {
        if (!response.ok) {
          const firstError = payload.errors ? Object.values(payload.errors)[0] : null;
          throw new Error(firstError || payload.message || 'The photo could not be saved.');
        }
        return payload;
      });
    });
  }

  if (uploadForm) {
    uploadForm.addEventListener('submit', function (event) {
      event.preventDefault();
      const fileInput = uploadForm.querySelector('input[type="file"]');
      if (!fileInput || !fileInput.files.length) { return; }
      const button = uploadForm.querySelector('button[type="submit"]');
      if (button) { button.disabled = true; }
      showError('');
      submit(uploadForm).then(function (payload) {
        applyAvatar(payload.avatar_url, payload.name);
        fileInput.value = '';
      }).catch(function (error) {
        showError(error.message);
      }).finally(function () {
        if (button) { button.disabled = false; }
      });
    });
  }

  if (removeForm) {
    removeForm.addEventListener('submit', function (event) {
      event.preventDefault();
      const button = removeForm.querySelector('button[type="submit"]');
      if (button) { button.disabled = true; }
      showError('');
      submit(removeForm).then(function (payload) {
        applyAvatar(null, payload.name);
      }).catch(function (error) {
        showError(error.message);
      }).finally(function () {
        if (button) { button.disabled = false; }
      });
    });
  }
})();
</script>
@endpush
