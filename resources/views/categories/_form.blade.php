@csrf
<div class="mb-3">
  <label class="form-label" for="name">Name</label>
  <input type="text" id="name" name="name" value="{{ old('name', $category->name ?? '') }}"
         class="form-control @error('name') is-invalid @enderror" required maxlength="100">
  @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
  <label class="form-label" for="default_priority">Automatic priority</label>
  <select id="default_priority" name="default_priority" class="form-select @error('default_priority') is-invalid @enderror" required>
    @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $v => $label)
      <option value="{{ $v }}" @selected(old('default_priority', $category->default_priority ?? 'medium') === $v)>{{ $label }}</option>
    @endforeach
  </select>
  <div class="form-text">New reports in this category start with this priority. Admins can still change it later.</div>
  @error('default_priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

<div class="mb-3">
  <label class="form-label" for="sort_order">Display order</label>
  <input type="number" id="sort_order" name="sort_order" min="0" max="999"
         value="{{ old('sort_order', $category->sort_order ?? 0) }}" class="form-control" style="max-width:140px">
</div>

<div class="form-check mb-3">
  <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
         @checked(old('is_active', $category->is_active ?? true))>
  <label class="form-check-label" for="is_active">Active (shown on the public report form)</label>
</div>

<button class="btn btn-primary">Save</button>
<a href="{{ route('categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
