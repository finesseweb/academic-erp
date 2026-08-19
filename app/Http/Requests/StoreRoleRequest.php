<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('role.create') ?? false;
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'code' => ['required', 'string', 'max:80', 'regex:/^[A-Z][A-Z0-9_]*$/', Rule::unique('roles')], 'description' => ['nullable', 'string', 'max:1000'], 'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]];
    }
}
