<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurriculumTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'sequence_no' => ['required', 'integer', 'min:1', 'max:999'],
            'name' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'sequence_no.required' => 'Term / Semester sequence is required.',
            'sequence_no.min' => 'Term / Semester sequence must start from 1.',
            'name.required' => 'Term / Semester name is required.',
        ];
    }
}
