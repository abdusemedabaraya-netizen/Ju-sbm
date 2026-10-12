@extends('layouts.admin')

@section('title', 'Edit category')
@section('page_title', 'Edit category')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categories</a></li>
  <li class="breadcrumb-item active" aria-current="page">Edit</li>
@endsection

@section('content')
<div class="card mb-4" style="max-width: 720px">
  <div class="card-body">
    <p class="text-secondary">Code: <code>{{ $category->slug }}</code> (cannot be changed)</p>
    <form method="POST" action="{{ route('categories.update', $category) }}">
      @method('PUT')
      @include('categories._form')
    </form>
  </div>
</div>
@endsection
