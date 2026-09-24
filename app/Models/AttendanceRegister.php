<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceRegister extends Model
{
    protected $fillable = ['class_schedule_id', 'academic_policy_id', 'status', 'revision_no', 'finalized_at', 'finalized_by', 'correction_reason', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['finalized_at' => 'datetime', 'revision_no' => 'integer'];
    }

    /** @return BelongsTo<ClassSchedule, $this> */
    public function classSchedule(): BelongsTo
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    /** @return BelongsTo<AcademicPolicy, $this> */
    public function academicPolicy(): BelongsTo
    {
        return $this->belongsTo(AcademicPolicy::class);
    }

    /** @return HasMany<AttendanceRecord, $this> */
    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
