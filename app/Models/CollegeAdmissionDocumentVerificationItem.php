<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeAdmissionDocumentVerificationItem extends Model
{
    protected $fillable = [
        'college_admission_document_verification_id', 'college_admission_application_field_value_id',
        'college_admission_form_field_id', 'field_label', 'file_name', 'status',
        'remarks', 'reviewed_at', 'reviewed_by',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionDocumentVerification::class, 'college_admission_document_verification_id');
    }

    public function fieldValue(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionApplicationFieldValue::class, 'college_admission_application_field_value_id');
    }
}
