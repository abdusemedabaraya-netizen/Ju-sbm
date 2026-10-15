<?php

namespace App\Http\Requests;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'   => ['nullable', Rule::in(Report::STATUSES)],
            'priority' => ['nullable', Rule::in(Report::PRIORITIES)],
            'note'     => ['nullable', 'string', 'max:1000'],
        ];
    }
}
