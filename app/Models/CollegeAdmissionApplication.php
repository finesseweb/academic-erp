<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\CollegeAdmissionFormTemplate;

class CollegeAdmissionApplication extends Model
{
    protected $fillable = [
        'college_id', 'college_admission_cycle_id', 'application_no', 'external_reference',
        'candidate_name', 'email', 'phone', 'date_of_birth', 'status', 'college_admission_form_template_id', 'admission_mode', 'entry_source',
        'application_fee_amount', 'application_fee_currency', 'application_fee_required', 'application_fee_rule_id', 'form_snapshot',
        'submitted_at', 'withdrawn_at', 'remarks', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'submitted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
        'application_fee_amount' => 'decimal:2',
        'application_fee_required' => 'boolean',
        'form_snapshot' => 'array',
    ];

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function admissionCycle(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionCycle::class, 'college_admission_cycle_id');
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionFormTemplate::class, 'college_admission_form_template_id');
    }

    public function fieldValues(): HasMany
    {
        return $this->hasMany(CollegeAdmissionApplicationFieldValue::class, 'college_admission_application_id');
    }

    public function choices(): HasMany
    {
        return $this->hasMany(CollegeAdmissionApplicationChoice::class, 'college_admission_application_id')
            ->orderBy('preference_no');
    }
}
