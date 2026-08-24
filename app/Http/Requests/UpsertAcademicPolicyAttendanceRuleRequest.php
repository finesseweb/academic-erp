<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertAcademicPolicyAttendanceRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('academic_policy.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'minimum_attendance_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'calculation_level' => ['required', Rule::in(['COURSE', 'TERM', 'OVERALL'])],
            'allow_condonation' => ['required', 'boolean'],
            'condonation_minimum_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'maximum_condonable_shortage_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'attendance_required_for_exam' => ['required', 'boolean'],
            'allow_special_exemption' => ['required', 'boolean'],
            'rounding_rule' => ['required', Rule::in(['NONE', 'NEAREST', 'FLOOR', 'CEIL'])],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
