<?php

namespace App\Http\Requests;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period'        => ['required', Rule::in(['daily', 'weekly', 'monthly', 'annual', 'custom'])],
            'from'          => ['nullable', 'date', 'required_if:period,custom'],
            'to'            => ['nullable', 'date', 'required_if:period,custom'],
            'status'        => ['nullable', Rule::in(array_merge(Report::STATUSES, ['open']))],
            'priority'      => ['nullable', Rule::in(Report::PRIORITIES)],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ];
    }
}
