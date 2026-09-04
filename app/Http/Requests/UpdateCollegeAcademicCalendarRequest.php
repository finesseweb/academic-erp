<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCollegeAcademicCalendarRequest extends FormRequest
{
    public function authorize(): bool
    {
        $college = $this->route('college');
        return $college && $this->user()?->hasCollegePermission('college_academic_calendar.update', $college->id);
    }

    public function rules(): array
    {
        return ['notes' => ['nullable', 'string', 'max:2000']];
    }
}
