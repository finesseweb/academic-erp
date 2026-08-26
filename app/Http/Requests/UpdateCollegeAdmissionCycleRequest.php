<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCollegeAdmissionCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $college = $this->route('college');

        return $college && $this->user()?->hasCollegePermission('college_admission_cycle.update', $college->id);
    }

    public function rules(): array
    {
        return [
            'college_program_offering_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'max:180'],
            'code' => ['required', 'string', 'max:80'],
            'application_start_date' => ['required', 'date'],
            'application_end_date' => ['required', 'date', 'after_or_equal:application_start_date'],
            'admission_start_date' => ['required', 'date', 'after_or_equal:application_start_date'],
            'admission_end_date' => ['required', 'date', 'after_or_equal:admission_start_date'],
            'remarks' => ['nullable', 'string', 'max:3000'],
        ];
    }
}
