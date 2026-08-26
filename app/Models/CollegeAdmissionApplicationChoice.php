<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeAdmissionApplicationChoice extends Model
{
    protected $fillable = [
        'college_admission_application_id', 'preference_no', 'college_program_intake_id',
        'college_program_reservation_plan_id', 'college_admission_selection_rule_id',
        'bucket_type', 'bucket_key', 'basis_capacity', 'eligibility_status',
        'eligibility_reason', 'eligibility_checked_at', 'eligibility_checked_by',
    ];

    protected $casts = [
        'preference_no' => 'integer',
        'basis_capacity' => 'integer',
        'eligibility_checked_at' => 'datetime',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionApplication::class, 'college_admission_application_id');
    }

    public function intake(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramIntake::class, 'college_program_intake_id');
    }

    public function reservationPlan(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramReservationPlan::class, 'college_program_reservation_plan_id');
    }

    public function selectionRule(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionSelectionRule::class, 'college_admission_selection_rule_id');
    }

    public function eligibilityCheckedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'eligibility_checked_by');
    }
}
