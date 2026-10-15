<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TrackReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tracking_number' => ['required', 'string', 'max:50'],
            'access_code'     => ['required', 'string', 'max:50'],
        ];
    }
}
