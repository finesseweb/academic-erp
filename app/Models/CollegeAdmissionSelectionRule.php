<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollegeAdmissionSelectionRule extends Model
{
    protected $fillable = [
        'college_program_intake_id', 'college_program_reservation_plan_id',
        'bucket_type', 'bucket_key', 'basis_capacity', 'version_no', 'name', 'code',
        'selection_mode', 'merit_weight_percent', 'entrance_weight_percent', 'interview_weight_percent',
        'minimum_merit_score', 'minimum_entrance_score', 'minimum_interview_score', 'minimum_final_score',
        'minimum_qualifying_score', 'roster_rule_reference', 'tie_breaker_rules',
        'notes', 'status', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'basis_capacity' => 'integer',
        'version_no' => 'integer',
        'merit_weight_percent' => 'decimal:2',
        'entrance_weight_percent' => 'decimal:2',
        'interview_weight_percent' => 'decimal:2',
        'minimum_merit_score' => 'decimal:3',
        'minimum_entrance_score' => 'decimal:3',
        'minimum_interview_score' => 'decimal:3',
        'minimum_final_score' => 'decimal:3',
        'minimum_qualifying_score' => 'decimal:3',
    ];

    public function intake(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramIntake::class, 'college_program_intake_id');
    }

    public function reservationPlan(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramReservationPlan::class, 'college_program_reservation_plan_id');
    }

    public function tieBreakers(): HasMany
    {
        return $this->hasMany(CollegeAdmissionSelectionRuleTieBreaker::class, 'college_admission_selection_rule_id')
            ->orderBy('priority');
    }
}
