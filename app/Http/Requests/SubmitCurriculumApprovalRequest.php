<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitCurriculumApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(
            'approval_request.submit'
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'approval_workflow_id' => [
                'required',
                'integer',
                'exists:approval_workflows,id',
            ],
        ];
    }
}
