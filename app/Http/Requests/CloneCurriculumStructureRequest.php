<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloneCurriculumStructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'academic_session_id' => ['required', 'integer', 'exists:academic_sessions,id'],
            'code' => ['required', 'string', 'max:60'],
            'name' => ['required', 'string', 'max:160'],
            'version' => ['required', 'string', 'max:30'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
