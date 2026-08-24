<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApprovalWorkflowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('approval_workflow.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9_-]+$/'],
            'applies_to' => ['required', Rule::in(['CURRICULUM', 'ACADEMIC_POLICY'])],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
