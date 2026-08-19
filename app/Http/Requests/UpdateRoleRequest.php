<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('role.update') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'code' => ['required', 'string', 'max:80', 'regex:/^[A-Z][A-Z0-9_]*$/', Rule::unique('roles')->ignore($this->route('role')->id)], 'description' => ['nullable', 'string', 'max:1000']];
    }
}
