<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InternalAssessmentActivity extends Model
{
    protected $fillable = ['internal_assessment_component_id', 'faculty_allocation_id', 'title', 'instructions', 'opens_at', 'closes_at', 'duration_minutes', 'status', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['opens_at' => 'datetime', 'closes_at' => 'datetime', 'duration_minutes' => 'integer'];
    }

    /** @return BelongsTo<InternalAssessmentComponent, $this> */
    public function component(): BelongsTo
    {
        return $this->belongsTo(InternalAssessmentComponent::class, 'internal_assessment_component_id');
    }

    /** @return BelongsTo<FacultyAllocation, $this> */
    public function facultyAllocation(): BelongsTo
    {
        return $this->belongsTo(FacultyAllocation::class);
    }

    /** @return HasMany<InternalAssessmentActivityStudent, $this> */
    public function students(): HasMany
    {
        return $this->hasMany(InternalAssessmentActivityStudent::class);
    }
}
