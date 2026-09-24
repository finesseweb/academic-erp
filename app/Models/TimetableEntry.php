<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimetableEntry extends Model
{
    protected $fillable = ['faculty_allocation_id', 'room_id', 'day_of_week', 'start_time', 'end_time', 'effective_from', 'effective_until', 'status', 'notes', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['day_of_week' => 'integer', 'effective_from' => 'date:Y-m-d', 'effective_until' => 'date:Y-m-d'];
    }

    /** @return BelongsTo<FacultyAllocation, $this> */
    public function facultyAllocation(): BelongsTo
    {
        return $this->belongsTo(FacultyAllocation::class);
    }

    /** @return BelongsTo<CollegeRoom, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(CollegeRoom::class, 'room_id');
    }

    /** @return HasMany<ClassSchedule, $this> */
    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }
}
