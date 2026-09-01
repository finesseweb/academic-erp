<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollegeAdmissionApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $college = $this->route('college');
        return $college && $this->user()?->hasCollegePermission('college_admission_application.create', $college->id);
    }

    public function rules(): array
    {
        return [
            'college_admission_cycle_id' => ['required', 'integer'],
            'admission_mode' => ['required', 'in:REGULAR,DIRECT'],
            'custom_fields' => ['nullable', 'array'],
            'external_reference' => ['nullable', 'string', 'max:120'],
            'candidate_name' => ['required', 'string', 'max:180'],
            'email' => ['nullable', 'email:rfc', 'max:190'],
            'phone' => ['nullable', 'string', 'max:40'],
            'date_of_birth' => ['required', 'date', 'before_or_equal:today'],
            'remarks' => ['nullable', 'string', 'max:3000'],
            'academic_preference' => ['required', 'array'],
            'academic_preference.discipline_id' => ['nullable', 'integer'],
            'academic_preference.specialization_id' => ['nullable', 'integer'],
            'academic_preference.course_choices' => ['nullable', 'array'],
            'academic_preference.course_choices.*' => ['nullable', 'array'],
            'academic_preference.course_choices.*.*' => ['integer'],
        ];
    }
}
