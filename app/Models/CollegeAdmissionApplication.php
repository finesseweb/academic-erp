<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollegeAdmissionApplication extends Model
{
    protected $fillable = [
        'college_id', 'college_admission_cycle_id', 'application_no', 'external_reference',
        'candidate_name', 'email', 'phone', 'date_of_birth', 'status',
        'submitted_at', 'withdrawn_at', 'remarks', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'submitted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function admissionCycle(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionCycle::class, 'college_admission_cycle_id');
    }

    public function choices(): HasMany
    {
        return $this->hasMany(CollegeAdmissionApplicationChoice::class, 'college_admission_application_id')
            ->orderBy('preference_no');
    }
}
