<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentEnrollment extends Model
{
    protected $fillable = [
        'student_id', 'college_id', 'college_program_offering_id', 'curriculum_id', 'discipline_id', 'specialization_id', 'admission_id',
        'batch_id', 'section_id', 'class_roll_no', 'class_roll_scope_key', 'source_type', 'status', 'enrolled_at', 'enrolled_by',
        'cancelled_at', 'cancelled_by', 'cancellation_reason',
    ];

    protected $casts = ['enrolled_at' => 'datetime', 'cancelled_at' => 'datetime'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramOffering::class, 'college_program_offering_id');
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(AcademicDiscipline::class, 'discipline_id');
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function attendanceRecords(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }

}
