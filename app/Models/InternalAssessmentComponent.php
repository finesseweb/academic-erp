<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalAssessmentComponent extends Model
{
    protected $fillable = ['course_offering_id', 'academic_policy_id', 'component_type', 'name', 'maximum_marks', 'weightage_percent', 'minimum_pass_marks', 'display_order', 'status', 'notes', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['maximum_marks' => 'decimal:2', 'weightage_percent' => 'decimal:2', 'minimum_pass_marks' => 'decimal:2', 'display_order' => 'integer'];
    }

    /** @return BelongsTo<CourseOffering, $this> */
    public function courseOffering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class);
    }

    /** @return BelongsTo<AcademicPolicy, $this> */
    public function academicPolicy(): BelongsTo
    {
        return $this->belongsTo(AcademicPolicy::class);
    }

    /** @return HasMany<InternalAssessmentActivity, $this> */
    public function activities(): HasMany
    {
        return $this->hasMany(InternalAssessmentActivity::class);
    }
}
