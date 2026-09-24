<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendanceEligibility extends Model
{
    protected $fillable = ['college_id', 'student_enrollment_id', 'course_offering_id', 'academic_policy_id', 'classes_held', 'classes_attended', 'attendance_percent', 'basis', 'is_eligible', 'finalized_at', 'finalized_by'];

    protected function casts(): array
    {
        return ['attendance_percent' => 'decimal:2', 'is_eligible' => 'boolean', 'finalized_at' => 'datetime'];
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
