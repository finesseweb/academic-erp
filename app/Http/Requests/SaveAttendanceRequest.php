<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['SAVE_DRAFT', 'FINALIZE'])],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_enrollment_id' => ['required', 'integer', 'distinct'],
            'records.*.attendance_status' => ['required', Rule::in(['PRESENT', 'ABSENT', 'LATE', 'EXCUSED'])],
            'records.*.remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
