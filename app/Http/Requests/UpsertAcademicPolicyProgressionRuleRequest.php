<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertAcademicPolicyProgressionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('academic_policy.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'evaluation_level' => ['required', Rule::in(['TERM', 'YEAR', 'PROGRAM_STAGE'])],
            'minimum_earned_credits' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'minimum_sgpa' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_cgpa' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'maximum_backlog_courses' => ['nullable', 'integer', 'min:0', 'max:999'],
            'mandatory_courses_must_be_passed' => ['required', 'boolean'],
            'allow_carry_forward' => ['required', 'boolean'],
            'allow_detention' => ['required', 'boolean'],
            'allow_year_back' => ['required', 'boolean'],
            'allow_readmission' => ['required', 'boolean'],
            'maximum_attempts_per_course' => ['nullable', 'integer', 'min:1', 'max:999'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
