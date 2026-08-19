<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRoleScopeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('scope.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'scope_type' => ['required', Rule::in(['UNIVERSITY', 'COLLEGE'])],
            'college_id' => [
                'nullable',
                'required_if:scope_type,COLLEGE',
                'integer',
                Rule::exists('colleges', 'id')->where(fn ($query) => $query->where('status', 'ACTIVE')),
            ],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ];
    }
}
