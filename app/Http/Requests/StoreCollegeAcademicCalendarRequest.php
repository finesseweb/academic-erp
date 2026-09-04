<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollegeAcademicCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        $college = $this->route('college');
        return $college && $this->user()?->hasCollegePermission('college_academic_calendar.create', $college->id);
    }

    public function rules(): array
    {
        return [
            'university_academic_calendar_id' => ['required', 'integer'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
