<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AcademicPolicy extends Model
{
    protected $fillable = [
        'university_id',
        'academic_session_id',
        'degree_level_id',
        'program_template_id',
        'curriculum_id',
        'parent_policy_id',
        'superseded_by_id',
        'name',
        'code',
        'version',
        'scope_type',
        'effective_from',
        'effective_to',
        'lifecycle_status',
        'approval_status',
        'is_current_version',
        'revision_type',
        'revision_reason',
        'revision_effective_from',
        'validation_hash',
        'validated_at',
        'validated_by',
        'description',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
            'revision_effective_from' => 'date',
            'validated_at' => 'datetime',
            'is_current_version' => 'boolean',
        ];
    }

    public function university(): BelongsTo { return $this->belongsTo(University::class); }
    public function academicSession(): BelongsTo { return $this->belongsTo(AcademicSession::class); }
    public function degreeLevel(): BelongsTo { return $this->belongsTo(DegreeLevel::class); }
    public function programTemplate(): BelongsTo { return $this->belongsTo(ProgramTemplate::class); }
    public function curriculum(): BelongsTo { return $this->belongsTo(Curriculum::class); }
    public function parentPolicy(): BelongsTo { return $this->belongsTo(self::class, 'parent_policy_id'); }
    public function supersededBy(): BelongsTo { return $this->belongsTo(self::class, 'superseded_by_id'); }
    public function revisions(): HasMany { return $this->hasMany(self::class, 'parent_policy_id'); }
    public function creditCompletionRule(): HasOne { return $this->hasOne(AcademicPolicyCreditCompletionRule::class); }
    public function attendanceRule(): HasOne { return $this->hasOne(AcademicPolicyAttendanceRule::class); }
    public function assessmentExamRule(): HasOne { return $this->hasOne(AcademicPolicyAssessmentExamRule::class); }
    public function gradingRule(): HasOne { return $this->hasOne(AcademicPolicyGradingRule::class); }
    public function gradeBands(): HasMany { return $this->hasMany(AcademicPolicyGradeBand::class)->orderBy('display_order')->orderByDesc('minimum_percent')->orderBy('id'); }
    public function progressionRuleSets(): HasMany { return $this->hasMany(AcademicPolicyProgressionRuleSet::class)->orderBy('display_order')->orderBy('id'); }
    public function creditCategoryRequirements(): HasMany
    {
        return $this->hasMany(AcademicPolicyCreditCategoryRequirement::class)
            ->orderBy('display_order')
            ->orderBy('id');
    }
}
