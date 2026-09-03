<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollegeAdmissionDocumentVerification extends Model
{
    protected $fillable = [
        'college_id', 'college_admission_application_id', 'status', 'notes',
        'finalized_at', 'finalized_by',
    ];

    protected $casts = ['finalized_at' => 'datetime'];

    public function application(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionApplication::class, 'college_admission_application_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CollegeAdmissionDocumentVerificationItem::class, 'college_admission_document_verification_id');
    }
}
