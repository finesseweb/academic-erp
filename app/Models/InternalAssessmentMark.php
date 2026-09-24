<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalAssessmentMark extends Model
{
    protected $fillable = ['internal_assessment_activity_student_id', 'result_status', 'marks_obtained', 'remarks', 'revision_no', 'entered_at', 'entered_by'];

    protected function casts(): array
    {
        return ['marks_obtained' => 'decimal:2', 'revision_no' => 'integer', 'entered_at' => 'datetime'];
    }

    /** @return BelongsTo<InternalAssessmentActivityStudent, $this> */
    public function activityStudent(): BelongsTo
    {
        return $this->belongsTo(InternalAssessmentActivityStudent::class, 'internal_assessment_activity_student_id');
    }
}
