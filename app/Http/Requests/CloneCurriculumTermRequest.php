<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloneCurriculumTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'target_curriculum_id' => ['required', 'integer', 'exists:curricula,id'],
            'sequence_no' => ['required', 'integer', 'min:1', 'max:65535'],
            'name' => ['required', 'string', 'max:100'],
        ];
    }
}
