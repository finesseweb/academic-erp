<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAcademicCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('academic_calendar.event_create') ?? false;
    }

    public function rules(): array
    {
        return $this->eventRules();
    }

    protected function eventRules(): array
    {
        return [
            'event_type' => ['required', Rule::in(['ACADEMIC', 'REGISTRATION', 'INSTRUCTION', 'EXAMINATION_WINDOW', 'HOLIDAY', 'VACATION', 'OTHER'])],
            'title' => ['required', 'string', 'max:180'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:5000'],
            'allow_college_override' => ['required', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }
}
