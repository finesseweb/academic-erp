<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUniversityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('university.update') ?? false;
    }

    public function rules(): array
    {
        $university = $this->route('university');

        return [
            'name' => ['required', 'string', 'max:180'],
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('universities')->ignore($university)],
            'short_name' => ['nullable', 'string', 'max:60'],
            'established_on' => ['nullable', 'date', 'before_or_equal:today'],
            'university_type' => ['nullable', Rule::in([
                'Central University',
                'State University',
                'Private University',
                'Deemed-to-be University',
                'Open University',
                'Institute of National Importance',
                'Other',
            ])],
            'university_type_other' => ['nullable', 'required_if:university_type,Other', 'string', 'max:60'],
            'accreditation' => ['nullable', 'string', 'max:120'],
            'official_email' => ['nullable', 'email:rfc', 'max:180'],
            'official_phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'address_line_1' => ['nullable', 'string', 'max:180'],
            'address_line_2' => ['nullable', 'string', 'max:180'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],
            'timezone' => ['required', 'timezone:all'],
        ];
    }
}
