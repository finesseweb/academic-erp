<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollegeProgramOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $college = $this->route('college');

        return $college
            && $this->user()?->hasCollegePermission(
                'college_program_offering.create',
                $college->id
            );
    }

    public function rules(): array
    {
        return [
            'program_template_id' => ['required', 'integer'],
            'curriculum_id' => ['required', 'integer'],
            'academic_session_id' => ['required', 'integer'],
        ];
    }
}
