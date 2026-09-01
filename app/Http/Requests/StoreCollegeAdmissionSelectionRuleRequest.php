<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollegeAdmissionSelectionRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $college = $this->route('college');
        return $college && $this->user()?->hasCollegePermission('college_admission_selection_rule.create', $college->id);
    }

    public function rules(): array
    {
        return $this->selectionRuleRules() + [
            'college_program_intake_id' => ['required', 'integer'],
            'bucket_key' => ['required', 'string', 'max:80'],
        ];
    }

    private function selectionRuleRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:60'],
            'selection_mode' => ['required', Rule::in(['MERIT', 'ENTRANCE', 'INTERVIEW', 'COMBINED'])],
            'merit_weight_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'entrance_weight_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'interview_weight_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'minimum_merit_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_entrance_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_interview_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_final_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'merit_sources' => ['nullable', 'array', 'max:10'],
            'merit_sources.*.label' => ['required', 'string', 'max:150'],
            'merit_sources.*.source_type' => ['required', Rule::in(['FORM_FIELD_PAIR'])],
            'merit_sources.*.obtained_field_id' => ['required', 'integer'],
            'merit_sources.*.maximum_field_id' => ['required', 'integer', 'different:merit_sources.*.obtained_field_id'],
            'merit_sources.*.weight_percent' => ['required', 'numeric', 'gt:0', 'max:100'],
            'roster_rule_reference' => ['nullable', 'string', 'max:255'],
            'tie_breaker_rules' => ['nullable', 'string', 'max:5000'],
            'tie_breakers' => ['nullable', 'array', 'max:10'],
            'tie_breakers.*.criterion' => ['required', Rule::in([
                'QUALIFYING_EXAM_SCORE', 'ENTRANCE_SCORE', 'INTERVIEW_SCORE', 'RELEVANT_SUBJECT_SCORE',
                'DATE_OF_BIRTH', 'APPLICATION_SUBMITTED_AT',
            ])],
            'tie_breakers.*.comparison_direction' => ['required', Rule::in(['ASC', 'DESC'])],
            'tie_breakers.*.criterion_reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
