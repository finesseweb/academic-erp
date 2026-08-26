<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCollegeReservationPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $college = $this->route('college');

        return $college && $this->user()?->hasCollegePermission(
            'college_reservation.update',
            $college->id
        );
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
