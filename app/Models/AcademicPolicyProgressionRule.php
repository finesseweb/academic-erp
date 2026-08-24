<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicPolicyProgressionRule extends Model
{
    protected $fillable = [
        'academic_policy_id',
        'evaluation_level',
        'minimum_earned_credits',
        'minimum_sgpa',
        'minimum_cgpa',
        'maximum_backlog_courses',
        'mandatory_courses_must_be_passed',
        'allow_carry_forward',
        'allow_detention',
        'allow_year_back',
        'allow_readmission',
        'maximum_attempts_per_course',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'minimum_earned_credits' => 'decimal:2',
            'minimum_sgpa' => 'decimal:3',
            'minimum_cgpa' => 'decimal:3',
            'maximum_backlog_courses' => 'integer',
            'mandatory_courses_must_be_passed' => 'boolean',
            'allow_carry_forward' => 'boolean',
            'allow_detention' => 'boolean',
            'allow_year_back' => 'boolean',
            'allow_readmission' => 'boolean',
            'maximum_attempts_per_course' => 'integer',
        ];
    }

    public function academicPolicy(): BelongsTo
    {
        return $this->belongsTo(AcademicPolicy::class);
    }
}
