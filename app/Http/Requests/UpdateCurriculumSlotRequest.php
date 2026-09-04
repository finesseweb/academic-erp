<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurriculumSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('curriculum.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'course_category_id' => ['required', 'integer', 'exists:course_categories,id'],
            'course_type_id' => ['required', 'integer', 'exists:course_types,id'],
            'credits' => ['required', 'numeric', 'min:0', 'max:99.99'],
            'credit_counting' => ['required', Rule::in(['COUNTABLE', 'NON_COUNTABLE'])],
            'name' => ['required', 'string', 'max:120'],
            'display_order' => ['required', 'integer', 'min:1', 'max:65535'],
            'selection_mode' => ['required', Rule::in(['MANDATORY', 'CHOICE'])],
            'min_selection' => [
                Rule::requiredIf(fn () => $this->input('selection_mode') === 'CHOICE'),
                'nullable',
                'integer',
                'min:1',
                'max:65535',
            ],
            'max_selection' => [
                Rule::requiredIf(fn () => $this->input('selection_mode') === 'CHOICE'),
                'nullable',
                'integer',
                'min:1',
                'max:65535',
                'gte:min_selection',
            ],
        ];
    }
}
