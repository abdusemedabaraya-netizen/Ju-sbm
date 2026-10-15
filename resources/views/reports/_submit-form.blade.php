@if ($errors->any())
    <div class="alert alert-danger"><strong>Please fix the following:</strong>
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

@guest
    <div class="alert alert-success">
        <i class="bi bi-incognito"></i> <strong>You are reporting anonymously.</strong>
        No account is needed, and we do not store your IP address with your report.
        Do not write your name in the text if you want to stay anonymous.
    </div>
@endguest

<div class="card shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('report.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="category">Type of concern *</label>
                    <select id="category" name="category" class="form-select @error('category') is-invalid @enderror" required>
                        <option value="">Select…</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->slug }}" @selected(old('category') === $c->slug)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="department_id">Department / unit involved *</label>
                    <select id="department_id" name="department_id" class="form-select @error('department_id') is-invalid @enderror" required>
                        <option value="">Select…</option>
                        @foreach ($departments as $d)
                            <option value="{{ $d->id }}" @selected(old('department_id') == $d->id)>{{ $d->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" value="{{ old('subject') }}" maxlength="255"
                           class="form-control @error('subject') is-invalid @enderror" required>
                    @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-12">
                    <label class="form-label" for="description">What happened? *</label>
                    <textarea id="description" name="description" rows="7" maxlength="10000"
                              class="form-control @error('description') is-invalid @enderror" required>{{ old('description') }}</textarea>
                    <div class="form-text">Who, what, where, when. The more detail, the easier it is to investigate.</div>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="incident_date">Date of incident</label>
                    <input type="date" id="incident_date" name="incident_date" value="{{ old('incident_date') }}"
                           max="{{ now()->toDateString() }}" class="form-control @error('incident_date') is-invalid @enderror">
                    @error('incident_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label" for="location">Location</label>
                    <input type="text" id="location" name="location" value="{{ old('location') }}" maxlength="255" class="form-control">
                </div>

                <div class="col-12">
                    <label class="form-label" for="files">Evidence (optional)</label>
                    <input type="file" id="files" name="files[]" multiple accept=".pdf,.docx,.jpg,.jpeg,.png,.mp4"
                           class="form-control @error('files') is-invalid @enderror @error('files.*') is-invalid @enderror">
                    <div class="form-text">PDF, DOCX, JPG, PNG or MP4. Up to {{ config('jiacrs.max_files') }} files, {{ intdiv(config('jiacrs.max_file_kb'), 1024) }} MB each.</div>
                    @error('files')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('files.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="alert alert-warning mt-2 mb-0 py-2 small">
                        <i class="bi bi-exclamation-triangle"></i>
                        Photos are cleaned of location and camera data automatically. <strong>Documents (PDF/DOCX) and videos can
                        contain your name or device details</strong>, so remove them before uploading if you want to stay anonymous.
                    </div>
                </div>

                @auth
                    <div class="col-12">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="anonymous" name="anonymous" value="1"
                                   @checked(old('anonymous', true))>
                            <label class="form-check-label" for="anonymous">
                                Submit anonymously (this report will <strong>not</strong> be linked to my account)
                            </label>
                        </div>
                    </div>
                @endauth
            </div>

            <div class="d-grid d-md-flex justify-content-md-end mt-4">
                <button class="btn btn-ju btn-lg px-5"><i class="bi bi-send"></i> Submit report</button>
            </div>
        </form>
    </div>
</div>
