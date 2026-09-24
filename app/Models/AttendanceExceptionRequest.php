<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceExceptionRequest extends Model
{
    protected $fillable = ['college_id', 'student_enrollment_id', 'course_offering_id', 'academic_policy_id', 'type', 'status', 'attendance_percent_snapshot', 'reason', 'supporting_reference', 'decision_remarks', 'requested_by', 'decided_at', 'decided_by'];

    protected function casts(): array
    {
        return ['attendance_percent_snapshot' => 'decimal:2', 'decided_at' => 'datetime'];
    }

    /** @return BelongsTo<StudentEnrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentEnrollment::class, 'student_enrollment_id');
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
}
