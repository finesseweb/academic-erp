<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertAcademicPolicyCreditCompletionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('academic_policy.update') ?? false;
    }

    public function rules(): array
    {
        $policy = $this->route('academicPolicy');
        $universityId = $policy?->university_id;

        return [
            'minimum_total_credits' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'minimum_completion_cgpa' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'maximum_program_duration_months' => ['nullable', 'integer', 'min:1', 'max:600'],
            'allow_credit_transfer' => ['required', 'boolean'],
            'maximum_credit_transfer_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'allow_credit_exemption' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],

            'category_requirements' => ['array', 'max:100'],
            'category_requirements.*.course_category_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('course_categories', 'id')->where(
                    fn ($query) => $query
                        ->where('university_id', $universityId)
                        ->where('status', 'ACTIVE')
                ),
            ],
            'category_requirements.*.minimum_credits' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'category_requirements.*.maximum_credits' => ['nullable', 'numeric', 'min:0.01', 'max:999999.99'],
            'category_requirements.*.display_order' => ['required', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
