@extends('layouts.admin')

@section('title', 'Roles & Permissions')
@section('page_title', 'Roles & Permissions')
@section('breadcrumb')
  <li class="breadcrumb-item active" aria-current="page">Roles &amp; Permissions</li>
@endsection

@php
  $capabilities = [
    'Super Admin'  => 'Full control: users and roles, settings, audit log, plus everything an Admin can do.',
    'Admin'        => 'Reviews reports, assigns investigators (status Under Review), rejects invalid reports, closes resolved cases, manages departments and categories, analytics and exports.',
    'Investigator' => 'Sees only assigned cases. Reviews evidence, records findings and recommendations, marks the case Investigating and Resolved. Cannot delete reports or users.',
    'User'         => 'Registered reporter: submits reports, views own reports, tracks progress and receives notifications.',
  ];
@endphp

@section('content')
@if ($errors->any())
  <div class="alert alert-danger"><ul class="mb-0">
    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
  </ul></div>
@endif

<div class="card mb-4">
  <div class="card-header"><h3 class="card-title">Roles</h3></div>
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead><tr><th>Role</th><th>What it can do</th><th class="text-end">Users</th></tr></thead>
      <tbody>
        @foreach ($roles as $role)
          <tr>
            <td><span class="badge text-bg-primary">{{ $role->name }}</span></td>
            <td>{{ $capabilities[$role->name] ?? '—' }}</td>
            <td class="text-end">{{ $role->users_count }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer text-secondary small">
    Access is enforced by role on every page. Anonymous reporters have no account and no role.
  </div>
</div>

<div class="card mb-4">
  <div class="card-header"><h3 class="card-title">Add an account</h3></div>
  <div class="card-body">
    <form method="POST" action="{{ route('roles.staff.store') }}" class="row g-3">
      @csrf
      <div class="col-md-3">
        <label class="form-label" for="name">Full name</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" class="form-control" required>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" class="form-control" required>
      </div>
      <div class="col-md-2">
        <label class="form-label" for="role">Role</label>
        <select id="role" name="role" class="form-select" required>
          @foreach ($allRoles as $r)
            <option value="{{ $r }}" @selected(old('role', 'Investigator') === $r)>{{ $r }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="password">Temporary password</label>
        <input type="password" id="password" name="password" class="form-control" autocomplete="new-password" required>
        <div class="form-text">At least 10 characters with letters and numbers.</div>
      </div>
      <div class="col-md-1 d-flex align-items-end">
        <button class="btn btn-primary w-100">Add</button>
      </div>
    </form>
  </div>
</div>

<div class="card mb-4">
  <div class="card-header"><h3 class="card-title">Users and their roles</h3></div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Name</th><th>Email</th><th>Current role</th><th style="width:300px">Change role</th></tr></thead>
      <tbody>
        @foreach ($staff as $u)
          <tr>
            <td>{{ $u->name }}</td>
            <td>{{ $u->email }}</td>
            <td>@forelse ($u->roles as $role)<span class="badge text-bg-primary">{{ $role->name }}</span> @empty <span class="text-secondary">none</span> @endforelse</td>
            <td>
              @if ($u->id === auth()->id())
                <span class="text-secondary">This is you</span>
              @else
                <form method="POST" action="{{ route('roles.assign', $u) }}" class="d-flex gap-2">
                  @csrf @method('PATCH')
                  <select name="role" class="form-select form-select-sm">
                    @foreach ($allRoles as $r)
                      <option value="{{ $r }}" @selected($u->hasRole($r))>{{ $r }}</option>
                    @endforeach
                  </select>
                  <button class="btn btn-sm btn-outline-primary">Save</button>
                </form>
              @endif
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @if ($staff->hasPages())<div class="card-footer">{{ $staff->links() }}</div>@endif
</div>
@endsection
