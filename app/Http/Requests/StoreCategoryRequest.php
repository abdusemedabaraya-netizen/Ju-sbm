<?php

namespace App\Http\Requests;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:100', 'unique:categories,name'],
            'default_priority' => ['required', Rule::in(Report::PRIORITIES)],
            'sort_order'       => ['nullable', 'integer', 'min:0', 'max:999'],
        ];
    }
}
