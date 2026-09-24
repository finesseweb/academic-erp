<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseOffering extends Model
{
    protected $fillable = [
        'batch_id', 'curriculum_course_mapping_id', 'status', 'notes', 'created_by', 'updated_by',
    ];

    /** @return BelongsTo<Batch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /** @return BelongsTo<CurriculumCourseMapping, $this> */
    public function curriculumCourseMapping(): BelongsTo
    {
        return $this->belongsTo(CurriculumCourseMapping::class);
    }

    public function facultyAllocations(): HasMany
    {
        return $this->hasMany(FacultyAllocation::class);
    }

    /** @return HasMany<InternalAssessmentComponent, $this> */
    public function internalAssessmentComponents(): HasMany
    {
        return $this->hasMany(InternalAssessmentComponent::class);
    }
}
