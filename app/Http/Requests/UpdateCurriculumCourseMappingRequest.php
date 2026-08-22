<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurriculumCourseMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'discipline_id' => ['required', 'integer', 'exists:academic_disciplines,id'],
            'specialization_id' => ['nullable', 'integer', 'exists:academic_disciplines,id'],
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ];
    }
}
