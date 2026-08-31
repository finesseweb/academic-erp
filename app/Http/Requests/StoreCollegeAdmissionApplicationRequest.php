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
            'choices' => ['required', 'array', 'min:1', 'max:10'],
            'choices.*.college_program_intake_id' => ['required', 'integer'],
            'choices.*.bucket_key' => ['required', 'string', 'max:80'],
        ];
    }
}
