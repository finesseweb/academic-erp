<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurriculumCourseMappingOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'display_order' => ['required', 'integer', 'min:1', 'max:65535'],
        ];
    }
}
