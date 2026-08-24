<?php

namespace App\Http\Requests;

class UpdateAcademicPolicyRequest extends StoreAcademicPolicyRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('academic_policy.update') ?? false;
    }
}
