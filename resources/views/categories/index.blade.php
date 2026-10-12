@extends('layouts.admin')

@section('title', 'Categories')
@section('page_title', 'Categories')
@section('breadcrumb')
  <li class="breadcrumb-item active" aria-current="page">Categories</li>
@endsection

@section('content')
<div class="card mb-4">
  <div class="card-header">
    <h3 class="card-title">Report categories and automatic priority</h3>
    <div class="card-tools"><a href="{{ route('categories.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New</a></div>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead><tr><th>Name</th><th>Code</th><th>Automatic priority</th><th>Status</th><th class="text-end">Reports</th><th></th></tr></thead>
      <tbody>
        @forelse ($categories as $c)
          <tr>
            <td>{{ $c->name }}</td>
            <td><code>{{ $c->slug }}</code></td>
            <td><x-priority-badge :priority="$c->default_priority" /></td>
            <td>{!! $c->is_active ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Inactive</span>' !!}</td>
            <td class="text-end">{{ $usage[$c->slug] ?? 0 }}</td>
            <td class="text-end">
              <a href="{{ route('categories.edit', $c) }}" class="btn btn-sm btn-outline-primary">Edit</a>
              <form method="POST" action="{{ route('categories.destroy', $c) }}" class="d-inline"
                    onsubmit="return confirm('Delete this category?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" @disabled(($usage[$c->slug] ?? 0) > 0)>Delete</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-center text-secondary py-4">No categories. Run <code>php artisan db:seed --class=CategorySeeder</code>.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="card-footer text-secondary small">
    A category used by existing reports cannot be deleted. Deactivate it to hide it from the public form.
  </div>
</div>
@endsection
