<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollegeAdmissionSeatAllocation extends Model
{
    protected $fillable = [
        'college_id', 'college_program_intake_id', 'bucket_type', 'bucket_key',
        'college_program_reservation_plan_id', 'college_admission_merit_entry_id',
        'college_admission_application_id', 'college_admission_application_choice_id',
        'college_admission_document_verification_id', 'college_admission_score_id', 'college_admission_selection_rule_id',
        'merit_rank', 'final_weighted_score', 'physical_seat_type',
        'candidate_reservation_category_id', 'candidate_category_source', 'candidate_category_code', 'candidate_category_name',
        'physical_reservation_category_id', 'physical_category_code', 'physical_category_name',
        'allocation_round', 'status', 'decision_note', 'allocated_at', 'allocated_by',
        'cancelled_at', 'cancelled_by', 'cancellation_reason',
    ];

    protected $casts = [
        'merit_rank' => 'integer',
        'final_weighted_score' => 'decimal:3',
        'allocation_round' => 'integer',
        'allocated_at' => 'datetime',
        'cancelled_at' => 'datetime',
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

    public function meritEntry(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionMeritEntry::class, 'college_admission_merit_entry_id');
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

    public function score(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionScore::class, 'college_admission_score_id');
    }

    public function selectionRule(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionSelectionRule::class, 'college_admission_selection_rule_id');
    }

    public function candidateReservationCategory(): BelongsTo
    {
        return $this->belongsTo(ReservationCategory::class, 'candidate_reservation_category_id');
    }

    public function physicalReservationCategory(): BelongsTo
    {
        return $this->belongsTo(ReservationCategory::class, 'physical_reservation_category_id');
    }

    public function horizontalCategories(): HasMany
    {
        return $this->hasMany(CollegeAdmissionSeatAllocationHorizontalCategory::class, 'college_admission_seat_allocation_id');
    }
}
