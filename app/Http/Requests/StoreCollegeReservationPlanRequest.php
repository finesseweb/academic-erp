<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollegeReservationPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $college = $this->route('college');

        return $college && $this->user()?->hasCollegePermission(
            'college_reservation.create',
            $college->id
        );
    }

    public function rules(): array
    {
        return [
            'college_program_intake_id' => ['required','integer'],
            'bucket_key' => ['required','string','max:80'],
            'notes' => ['nullable','string','max:5000'],
        ];
    }
}
