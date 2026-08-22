<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApprovalWorkflowStageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('approval_workflow.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'sequence_no' => ['required', 'integer', 'min:1', 'max:99'],
            'name' => ['required', 'string', 'max:120'],
            'approver_role_id' => ['required', 'integer', 'exists:roles,id'],
            'remarks_required_on_reject' => ['required', 'boolean'],
            'remarks_required_on_return' => ['required', 'boolean'],
        ];
    }
}
