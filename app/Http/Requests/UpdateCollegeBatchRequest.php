<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCollegeBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        $college = $this->route('college');

        return $college
            && $this->user()?->hasCollegePermission('college_batch.update', $college->id);
    }

    public function rules(): array
    {
        return [
            'college_program_offering_id' => ['required', 'integer'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._\/-]+$/'],
            'name' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex' => 'Batch code may contain only letters, numbers, dot, underscore, slash and hyphen.',
        ];
    }
}
