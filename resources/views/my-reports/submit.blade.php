@extends('layouts.admin')

@section('title', 'Submit a report')

@section('page_title', 'Submit a report')

@section('breadcrumb')
    <li class="breadcrumb-item active" aria-current="page">Submit a report</li>
@endsection

@section('content')
@push('styles')
    @vite(['resources/css/app.css'])
@endpush

<div class="container-fluid">
    @include('reports._submit-form')
</div>
@endsection
