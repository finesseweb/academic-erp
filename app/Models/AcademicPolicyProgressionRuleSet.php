<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AcademicPolicyProgressionRuleSet extends Model
{
    protected $fillable = [
        'academic_policy_id',
        'curriculum_id',
        'name',
        'applies_to_all_stages',
        'evaluation_mode',
        'target_curriculum_term_id',
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
        'display_order',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'applies_to_all_stages' => 'boolean',
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
            'display_order' => 'integer',
        ];
    }

    public function academicPolicy(): BelongsTo { return $this->belongsTo(AcademicPolicy::class); }
    public function curriculum(): BelongsTo { return $this->belongsTo(Curriculum::class); }
    public function targetTerm(): BelongsTo { return $this->belongsTo(CurriculumTerm::class, 'target_curriculum_term_id'); }

    public function sourceTerms(): BelongsToMany
    {
        return $this->belongsToMany(
            CurriculumTerm::class,
            'academic_policy_progression_rule_terms',
            'progression_rule_set_id',
            'curriculum_term_id'
        )->withPivot('display_order')->withTimestamps()->orderBy('pivot_display_order');
    }
}
