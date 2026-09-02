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
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user')->id)],
            'mobile' => ['nullable', 'string', 'max:30'],
            'account_type' => ['required', Rule::in(['SYSTEM_ADMIN', 'UNIVERSITY_STAFF', 'COLLEGE_STAFF', 'OTHER'])],
        ];
    }

    public function messages(): array
    {
        return ['email.unique' => 'Another user account already uses this email.'];
    }
}
