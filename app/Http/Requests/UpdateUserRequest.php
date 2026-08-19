<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('user.update') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')->ignore($this->route('user')->id)], 'mobile' => ['nullable', 'string', 'max:30'], 'account_type' => ['required', Rule::in(['SYSTEM_ADMIN', 'UNIVERSITY_STAFF', 'COLLEGE_STAFF', 'OTHER'])]];
    }
}
