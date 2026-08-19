<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollegeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('college.create') ?? false;
    }

    public function rules(): array
    {
        return $this->commonRules();
    }

    protected function commonRules(?int $collegeId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:180'],
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('colleges')->ignore($collegeId)],
            'affiliation_type' => ['required', Rule::in(['Constituent', 'Affiliated', 'Autonomous', 'Government', 'Private Aided', 'Private Unaided', 'Other'])],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
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
