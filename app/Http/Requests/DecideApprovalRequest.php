<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(
            'approval_request.decide'
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'decision' => [
                'required',
                Rule::in(['APPROVE', 'REJECT', 'RETURN']),
            ],
            'remarks' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
