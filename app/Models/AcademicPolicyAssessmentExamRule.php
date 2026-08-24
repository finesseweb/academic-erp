<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicPolicyAssessmentExamRule extends Model
{
    protected $fillable = [
        'academic_policy_id', 'minimum_overall_pass_percent',
        'require_separate_component_pass', 'absence_result',
        'allow_grace_marks', 'maximum_grace_marks',
        'allow_improvement_exam', 'allow_supplementary_exam',
        'notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'minimum_overall_pass_percent' => 'decimal:2',
            'require_separate_component_pass' => 'boolean',
            'allow_grace_marks' => 'boolean',
            'maximum_grace_marks' => 'decimal:2',
            'allow_improvement_exam' => 'boolean',
            'allow_supplementary_exam' => 'boolean',
        ];
    }

    public function academicPolicy(): BelongsTo { return $this->belongsTo(AcademicPolicy::class); }
}
