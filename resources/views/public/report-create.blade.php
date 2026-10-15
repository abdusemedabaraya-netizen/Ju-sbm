@extends('layouts.public')

@section('title', 'Submit a report')

@section('content')
<section class="page-head py-5">
    <div class="container">
        <h1 class="h2 mb-1">Submit a report</h1>
        <p class="mb-0 opacity-75">Report corruption, fraud, nepotism, abuse of power or harassment safely and confidentially.</p>
    </div>
</section>

<div class="container py-4" style="max-width: 860px">
    @include('reports._submit-form')
</div>
@endsection
