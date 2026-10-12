@extends('layouts.public')

@section('title', 'Reporting temporarily closed')

@section('content')
<div class="container py-5" style="max-width: 640px">
  <div class="card shadow-sm">
    <div class="card-body p-4 text-center">
      <i class="bi bi-pause-circle text-warning" style="font-size: 3rem"></i>
      <h1 class="h4 mt-2">New reports are temporarily closed</h1>
      <p class="text-secondary mb-3">Please try again later.
        @if (config('jiacrs.contact_email'))
          You can also contact the Integrity Office at <strong>{{ config('jiacrs.contact_email') }}</strong>.
        @endif
      </p>
      <a href="{{ route('track.index') }}" class="btn btn-outline-primary">Track an existing report</a>
    </div>
  </div>
</div>
@endsection
