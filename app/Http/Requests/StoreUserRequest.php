<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('user.create') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users')], 'mobile' => ['nullable', 'string', 'max:30'], 'account_type' => ['required', Rule::in(['SYSTEM_ADMIN', 'UNIVERSITY_STAFF', 'COLLEGE_STAFF', 'OTHER'])], 'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])], 'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()]];
    }
}
