<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ClassSchedule extends Model
{
    protected $fillable = ['timetable_entry_id', 'class_date', 'start_time', 'end_time', 'room_id', 'status', 'notes', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['class_date' => 'date:Y-m-d'];
    }

    /** @return BelongsTo<TimetableEntry, $this> */
    public function timetableEntry(): BelongsTo
    {
        return $this->belongsTo(TimetableEntry::class);
    }

    /** @return BelongsTo<CollegeRoom, $this> */
    public function room(): BelongsTo
    {
        return $this->belongsTo(CollegeRoom::class, 'room_id');
    }

    public function attendanceRegister(): HasOne
    {
        return $this->hasOne(AttendanceRegister::class);
    }
}
