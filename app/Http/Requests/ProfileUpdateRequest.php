<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Staff e-mail is managed by the Super Admin only (a changed address needs
     * re-verification and could lock a staff member out), so reporters and
     * admins get an editable e-mail field and staff members do not.
     */
    public function rules(): array
    {
        $rules = [
            'name'          => ['required', 'string', 'max:255'],
            'phone'         => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]{6,30}$/'],
            'job_title'     => ['nullable', 'string', 'max:100'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'bio'           => ['nullable', 'string', 'max:500'],
        ];

        if (! $this->user()->isStaff()) {
            $rules['email'] = ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)];
        }

        return $rules;
    }
}
