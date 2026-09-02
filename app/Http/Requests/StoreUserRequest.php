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

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => is_string($this->input('name')) ? trim($this->input('name')) : $this->input('name'),
            'email' => is_string($this->input('email')) ? mb_strtolower(trim($this->input('email'))) : $this->input('email'),
            'mobile' => is_string($this->input('mobile')) ? trim($this->input('mobile')) : $this->input('mobile'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'mobile' => ['nullable', 'string', 'max:30'],
            'account_type' => ['required', Rule::in(['SYSTEM_ADMIN', 'UNIVERSITY_STAFF', 'COLLEGE_STAFF', 'OTHER'])],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
            'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return ['email.unique' => 'A user account with this email already exists. Duplicate users cannot be created.'];
    }
}
