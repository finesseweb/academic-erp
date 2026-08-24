<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertAcademicPolicyAssessmentExamRuleRequest extends FormRequest
{
    public function authorize(): bool { return $this->user()?->hasPermission('academic_policy.update') ?? false; }

    public function rules(): array
    {
        return [
            'minimum_overall_pass_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'require_separate_component_pass' => ['required', 'boolean'],
            'absence_result' => ['required', Rule::in(['FAIL', 'INCOMPLETE', 'AS_PER_EXAM_RULE'])],
            'allow_grace_marks' => ['required', 'boolean'],
            'maximum_grace_marks' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'allow_improvement_exam' => ['required', 'boolean'],
            'allow_supplementary_exam' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
