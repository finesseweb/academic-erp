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

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function curriculumCourseMapping(): BelongsTo
    {
        return $this->belongsTo(CurriculumCourseMapping::class);
    }

    public function facultyAllocations(): HasMany
    {
        return $this->hasMany(FacultyAllocation::class);
    }
}
