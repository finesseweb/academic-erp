<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeAdmissionMeritEntry extends Model
{
    protected $fillable = [
        'college_id', 'college_program_intake_id', 'bucket_key',
        'college_admission_application_id', 'college_admission_application_choice_id',
        'college_admission_score_id', 'college_admission_selection_rule_id',
        'generation_batch', 'rank', 'final_weighted_score', 'tie_break_snapshot',
        'generated_at', 'generated_by',
    ];

    protected $casts = [
        'rank' => 'integer',
        'final_weighted_score' => 'decimal:3',
        'tie_break_snapshot' => 'array',
        'generated_at' => 'datetime',
    ];

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function intake(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramIntake::class, 'college_program_intake_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionApplication::class, 'college_admission_application_id');
    }

    public function choice(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionApplicationChoice::class, 'college_admission_application_choice_id');
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
