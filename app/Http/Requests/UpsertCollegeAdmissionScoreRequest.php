<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpsertCollegeAdmissionScoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'merit_raw_score' => ['nullable','numeric','min:0','max:999999.999'],
            'merit_max_score' => ['nullable','numeric','gt:0','max:999999.999'],
            'entrance_raw_score' => ['nullable','numeric','min:0','max:999999.999'],
            'entrance_max_score' => ['nullable','numeric','gt:0','max:999999.999'],
            'notes' => ['nullable','string','max:3000'],
        ];
    }
}
