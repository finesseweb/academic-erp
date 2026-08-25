<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCollegeProgramIntakeAllocationRequest extends FormRequest
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
            'seat_scope_type' => ['required', 'string'],
            'parent_allocation_id' => ['nullable', 'integer'],
            'discipline_id' => ['required', 'integer'],
            'specialization_id' => ['nullable', 'integer'],
            'seat_capacity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }
}
