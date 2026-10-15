<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isStaff();
    }

    public function rules(): array
    {
        return [
            'files'    => ['required', 'array', 'max:' . config('jiacrs.max_files')],
            'files.*'  => ['file', 'mimes:pdf,docx,jpg,jpeg,png,mp4', 'max:' . config('jiacrs.max_file_kb')],
        ];
    }
}
