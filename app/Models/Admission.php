<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Admission extends Model
{
    protected $fillable = [
        'college_id', 'college_program_intake_id', 'college_program_reservation_plan_id', 'curriculum_id', 'college_admission_application_id',
        'college_admission_application_choice_id', 'college_admission_document_verification_id',
        'college_admission_seat_allocation_id', 'college_admission_merit_entry_id',
        'college_admission_score_id', 'college_admission_selection_rule_id',
        'admission_no', 'status', 'decision_note', 'confirmed_at', 'confirmed_by',
        'revoked_at', 'revoked_by', 'revocation_reason',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function intake(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramIntake::class, 'college_program_intake_id');
    }


    public function reservationPlan(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramReservationPlan::class, 'college_program_reservation_plan_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionApplication::class, 'college_admission_application_id');
    }

    public function choice(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionApplicationChoice::class, 'college_admission_application_choice_id');
    }

    public function documentVerification(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionDocumentVerification::class, 'college_admission_document_verification_id');
    }

    public function seatAllocation(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionSeatAllocation::class, 'college_admission_seat_allocation_id');
    }

    public function meritEntry(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionMeritEntry::class, 'college_admission_merit_entry_id');
    }

    public function score(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionScore::class, 'college_admission_score_id');
    }

    public function selectionRule(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionSelectionRule::class, 'college_admission_selection_rule_id');
    }
}
