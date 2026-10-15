<?php

namespace App\Http\Requests;

use App\Models\Report;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportFilterRequest extends FormRequest
{
    public const VIEWS = ['open', 'new', 'under_review', 'in_progress', 'high_risk', 'resolved_closed', 'rejected'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'        => ['nullable', 'string', 'max:255'],
            'status'        => ['nullable', Rule::in(Report::STATUSES)],
            'priority'      => ['nullable', Rule::in(Report::PRIORITIES)],
            'view'          => ['nullable', Rule::in(self::VIEWS)],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')],
            'category'      => ['nullable', 'string', 'max:50', Rule::exists('categories', 'slug')],
            'date_from'     => ['nullable', 'date'],
            'date_to'       => ['nullable', 'date'],
        ];
    }
}
