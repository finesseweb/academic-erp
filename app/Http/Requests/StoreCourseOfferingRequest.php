<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCourseOfferingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $college = $this->route('college');
        return $college && $this->user()?->hasCollegePermission('college_course_offering.create', $college->id);
    }

    public function rules(): array
    {
        return [
            'batch_id' => ['required', 'integer'],
            'discipline_id' => ['required', 'integer'],
            'term_id' => ['required', 'integer'],
            'selected_choice_mapping_ids' => ['sometimes', 'array'],
            'selected_choice_mapping_ids.*' => ['integer', 'distinct'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
