<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvestigationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_id' => [
                'required',
                Rule::exists('reports', 'id')->whereNull('deleted_at'),
                Rule::unique('investigations', 'report_id'),
            ],
            'investigator_id' => ['required', Rule::exists('users', 'id')],
        ];
    }
}
