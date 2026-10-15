<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Services\SiteSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    /** Public and authenticated users may both submit reports. */
    public function authorize(): bool
    {
        return true;
    }

    /** Site settings must be applied before the file-limit rules are built. */
    protected function prepareForValidation(): void
    {
        SiteSettings::applyToConfig();
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', Rule::exists('departments', 'id')],
            'category'      => ['required', 'string', Rule::in(Category::active()->pluck('slug')->all())],
            'subject'       => ['required', 'string', 'max:255'],
            'description'   => ['required', 'string', 'max:10000'],
            'incident_date' => ['nullable', 'date', 'before_or_equal:today'],
            'location'      => ['nullable', 'string', 'max:255'],
            'anonymous'     => ['nullable', 'boolean'],
            'files'         => ['nullable', 'array', 'max:' . config('jiacrs.max_files')],
            'files.*'       => ['file', 'mimes:pdf,docx,jpg,jpeg,png,mp4', 'max:' . config('jiacrs.max_file_kb')],
        ];
    }
}
