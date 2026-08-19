<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuthorizedSignatoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('authorized_signatory.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:180'],
            'designation' => ['required', 'string', 'max:120'],
            'authority_type' => ['required', Rule::in(['GENERAL', 'ACADEMIC_RECORDS', 'EXAMINATION', 'CERTIFICATES', 'FINANCE'])],
            'email' => ['nullable', 'email:rfc', 'max:180'],
            'phone' => ['nullable', 'string', 'max:30'],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
