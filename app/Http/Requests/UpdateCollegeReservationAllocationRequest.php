<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCollegeReservationAllocationRequest extends FormRequest
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
            'reservation_category_id' => ['required','integer'],
            'seat_capacity' => ['required','integer','min:1','max:1000000'],
        ];
    }
}
