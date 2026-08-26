<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeAdmissionScore extends Model
{
    protected $fillable = [
        'college_admission_application_id', 'college_admission_application_choice_id',
        'college_admission_selection_rule_id', 'merit_raw_score', 'merit_max_score',
        'merit_normalized_score', 'entrance_raw_score', 'entrance_max_score',
        'entrance_normalized_score', 'interview_normalized_score', 'final_weighted_score',
        'qualification_status', 'qualification_reason', 'notes', 'scored_at', 'scored_by', 'updated_by',
    ];

    protected $casts = [
        'merit_raw_score'=>'decimal:3','merit_max_score'=>'decimal:3','merit_normalized_score'=>'decimal:3',
        'entrance_raw_score'=>'decimal:3','entrance_max_score'=>'decimal:3','entrance_normalized_score'=>'decimal:3',
        'interview_normalized_score'=>'decimal:3','final_weighted_score'=>'decimal:3','scored_at'=>'datetime',
    ];

    public function application(): BelongsTo { return $this->belongsTo(CollegeAdmissionApplication::class, 'college_admission_application_id'); }
    public function choice(): BelongsTo { return $this->belongsTo(CollegeAdmissionApplicationChoice::class, 'college_admission_application_choice_id'); }
    public function selectionRule(): BelongsTo { return $this->belongsTo(CollegeAdmissionSelectionRule::class, 'college_admission_selection_rule_id'); }
}
