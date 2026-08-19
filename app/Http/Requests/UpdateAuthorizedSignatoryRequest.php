<?php

namespace App\Http\Requests;

class UpdateAuthorizedSignatoryRequest extends StoreAuthorizedSignatoryRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('authorized_signatory.update') ?? false;
    }
}
