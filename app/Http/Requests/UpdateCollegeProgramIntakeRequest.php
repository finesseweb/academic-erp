<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCollegeProgramIntakeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $college = $this->route('college');

        return $college
            && $this->user()?->hasCollegePermission(
                'college_program_intake.update',
                $college->id
            );
    }

    public function rules(): array
    {
        return [
            'approved_capacity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'allocation_mode' => [
                'required',
                Rule::in(['PROGRAM', 'DISCIPLINE']),
            ],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
