<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertAcademicPolicyProgressionRuleSetsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('academic_policy.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'rule_sets' => ['present', 'array', 'max:50'],

            'rule_sets.*.name' => ['required', 'string', 'max:150'],
            'rule_sets.*.applies_to_all_stages' => ['required', 'boolean'],
            'rule_sets.*.curriculum_id' => ['nullable', 'integer'],
            'rule_sets.*.source_term_ids' => ['array', 'max:50'],
            'rule_sets.*.source_term_ids.*' => ['integer'],
            'rule_sets.*.target_curriculum_term_id' => ['nullable', 'integer'],
            'rule_sets.*.evaluation_mode' => ['required', Rule::in(['COMBINED', 'EACH_TERM'])],

            'rule_sets.*.minimum_earned_credits' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'rule_sets.*.minimum_sgpa' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rule_sets.*.minimum_cgpa' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rule_sets.*.maximum_backlog_courses' => ['nullable', 'integer', 'min:0', 'max:999'],
            'rule_sets.*.mandatory_courses_must_be_passed' => ['required', 'boolean'],

            'rule_sets.*.allow_carry_forward' => ['required', 'boolean'],
            'rule_sets.*.allow_detention' => ['required', 'boolean'],
            'rule_sets.*.allow_year_back' => ['required', 'boolean'],
            'rule_sets.*.allow_readmission' => ['required', 'boolean'],
            'rule_sets.*.maximum_attempts_per_course' => ['nullable', 'integer', 'min:1', 'max:999'],
            'rule_sets.*.notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
