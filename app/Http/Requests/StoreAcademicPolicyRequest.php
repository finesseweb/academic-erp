<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicPolicyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('academic_policy.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'academic_session_id' => ['required', 'integer'],
            'program_template_id' => ['nullable', 'integer'],
            'curriculum_id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:180'],
            'code' => ['required', 'string', 'max:100'],
            'version' => ['required', 'string', 'max:30'],
            'scope_type' => ['required', Rule::in(['UNIVERSITY', 'PROGRAM_TEMPLATE', 'CURRICULUM'])],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
