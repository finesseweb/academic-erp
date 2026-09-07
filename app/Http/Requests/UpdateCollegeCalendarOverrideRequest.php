<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCollegeCalendarOverrideRequest extends FormRequest
{
    public function authorize(): bool
    {
        $college = $this->route('college');
        return $college && $this->user()?->hasCollegePermission('college_academic_calendar.override_update', $college->id);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'description' => ['nullable', 'string', 'max:4000'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}
