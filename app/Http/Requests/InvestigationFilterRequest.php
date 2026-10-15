<?php

namespace App\Http\Requests;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvestigationFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tab'             => ['nullable', Rule::in(['all', 'active', 'completed'])],
            'search'          => ['nullable', 'string', 'max:100'],
            'status'          => ['nullable', Rule::in(array_merge(Report::ACTIVE_STATUSES, Report::DONE_STATUSES))],
            'priority'        => ['nullable', Rule::in(Report::PRIORITIES)],
            'investigator_id' => ['nullable', 'integer'],
        ];
    }
}
