@extends('layouts.admin')

@section('title', 'Settings')
@section('page_title', 'Settings')
@section('breadcrumb')
  <li class="breadcrumb-item active" aria-current="page">Settings</li>
@endsection

@section('content')
@if ($errors->any())
  <div class="alert alert-danger"><ul class="mb-0">
    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
  </ul></div>
@endif

<form method="POST" action="{{ route('settings.update') }}" style="max-width: 820px">
  @csrf @method('PUT')

  <div class="card mb-4">
    <div class="card-header"><h3 class="card-title">Organisation</h3></div>
    <div class="card-body row g-3">
      <div class="col-md-12">
        <label class="form-label" for="site_name">System name</label>
        <input type="text" id="site_name" name="site_name" value="{{ old('site_name', $s['site_name']) }}" class="form-control" required maxlength="100">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="contact_email">Integrity office email</label>
        <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email', $s['contact_email']) }}" class="form-control">
      </div>
      <div class="col-md-6">
        <label class="form-label" for="contact_phone">Integrity office phone</label>
        <input type="text" id="contact_phone" name="contact_phone" value="{{ old('contact_phone', $s['contact_phone']) }}" class="form-control" maxlength="30">
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><h3 class="card-title">Reporting</h3></div>
    <div class="card-body">
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" role="switch" id="public_reporting_enabled" name="public_reporting_enabled" value="1"
               @checked(old('public_reporting_enabled', $s['public_reporting_enabled']))>
        <label class="form-check-label" for="public_reporting_enabled">Accept new reports on the public form</label>
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label" for="max_files">Maximum files per report</label>
          <input type="number" id="max_files" name="max_files" min="1" max="10" value="{{ old('max_files', $s['max_files']) }}" class="form-control" required>
        </div>
        <div class="col-md-6">
          <label class="form-label" for="max_file_mb">Maximum size per file (MB)</label>
          <input type="number" id="max_file_mb" name="max_file_mb" min="1" max="50" value="{{ old('max_file_mb', $s['max_file_mb']) }}" class="form-control" required>
        </div>
      </div>
      <div class="form-text mt-2">
        The server also has its own limits (<code>upload_max_filesize</code> and <code>post_max_size</code> in <code>php.ini</code>).
        The default is only 2 MB, so raise both to at least 25M to allow 20 MB files.
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header"><h3 class="card-title">Notifications</h3></div>
    <div class="card-body">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox" role="switch" id="mail_notifications" name="mail_notifications" value="1"
               @checked(old('mail_notifications', $s['mail_notifications']))>
        <label class="form-check-label" for="mail_notifications">Also send notifications by e-mail</label>
      </div>
      <div class="form-text">Needs working SMTP settings in the <code>.env</code> file. In-app notifications are always on.</div>
    </div>
  </div>

  <button class="btn btn-primary">Save settings</button>
</form>
@endsection
