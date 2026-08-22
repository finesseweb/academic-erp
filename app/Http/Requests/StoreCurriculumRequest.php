<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurriculumRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'program_template_id' => ['required', 'integer', 'exists:program_templates,id'],
            'academic_session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'code' => ['required', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:160'],
            'version' => ['required', 'string', 'max:30'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'lifecycle_status' => ['required', Rule::in(['DRAFT', 'ACTIVE', 'RETIRED'])],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
