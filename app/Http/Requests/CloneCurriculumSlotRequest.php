<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloneCurriculumSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'target_curriculum_id' => ['required', 'integer', 'exists:curricula,id'],
            'target_term_id' => ['required', 'integer', 'exists:curriculum_terms,id'],
            'name' => ['required', 'string', 'max:120'],
            'display_order' => ['required', 'integer', 'min:1', 'max:65535'],
        ];
    }
}
