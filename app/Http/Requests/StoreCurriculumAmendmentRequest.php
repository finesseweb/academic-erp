<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCurriculumAmendmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasPermission('curriculum.update');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50'],
            'version' => ['required', 'string', 'max:30'],
            'revision_type' => [
                'required',
                'string',
                Rule::in([
                    'CORRECTION',
                    'COURSE_REPLACEMENT',
                    'TERM_SEMESTER_CHANGE',
                    'SLOT_CHANGE',
                    'CREDIT_CHANGE',
                    'STRUCTURE_CHANGE',
                    'OTHER',
                ]),
            ],
            'revision_reason' => ['required', 'string', 'max:2000'],
            'revision_effective_from' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'revision_type.required' => 'Select the type of Curriculum amendment.',
            'revision_reason.required' => 'Enter the academic reason for this amendment.',
        ];
    }
}
