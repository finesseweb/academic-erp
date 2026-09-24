<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InternalAssessmentActivityStudent extends Model
{
    protected $fillable = ['internal_assessment_activity_id', 'student_enrollment_id', 'assigned_at', 'assigned_by'];

    protected function casts(): array
    {
        return ['assigned_at' => 'datetime'];
    }

    /** @return BelongsTo<InternalAssessmentActivity, $this> */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(InternalAssessmentActivity::class, 'internal_assessment_activity_id');
    }

    /** @return BelongsTo<StudentEnrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
    }

    /** @return HasOne<InternalAssessmentMark, $this> */
    public function mark(): HasOne
    {
        return $this->hasOne(InternalAssessmentMark::class);
    }
}
