<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollegeCalendarOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        $college = $this->route('college');
        return $college && $this->user()?->hasCollegePermission('college_academic_calendar.override_create', $college->id);
    }

    public function rules(): array
    {
        return [
            'academic_calendar_event_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:180'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:4000'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
