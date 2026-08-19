<?php

namespace App\Http\Requests;

class UpdateCollegeRequest extends StoreCollegeRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('college.update') ?? false;
    }

    public function rules(): array
    {
        return $this->commonRules($this->route('college')->id);
    }
}
