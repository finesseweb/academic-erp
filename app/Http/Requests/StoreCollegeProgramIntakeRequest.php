<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollegeProgramIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $college = $this->route('college');

        return $college
            && $this->user()?->hasCollegePermission(
                'college_program_intake.create',
                $college->id
            );
    }

    public function rules(): array
    {
        return [
            'college_program_offering_id' => ['required', 'integer'],
            'approved_capacity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'allocation_mode' => [
                'required',
                Rule::in(['PROGRAM', 'DISCIPLINE']),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
