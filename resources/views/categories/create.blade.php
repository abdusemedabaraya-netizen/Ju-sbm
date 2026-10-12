@extends('layouts.admin')

@section('title', 'New category')
@section('page_title', 'New category')
@section('breadcrumb')
  <li class="breadcrumb-item"><a href="{{ route('categories.index') }}">Categories</a></li>
  <li class="breadcrumb-item active" aria-current="page">New</li>
@endsection

@section('content')
<div class="card mb-4" style="max-width: 720px">
  <div class="card-body">
    <form method="POST" action="{{ route('categories.store') }}">
      @include('categories._form')
    </form>
  </div>
</div>
@endsection
