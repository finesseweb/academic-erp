<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeCalendarOverride extends Model
{
    protected $fillable = [
        'college_academic_calendar_id', 'academic_calendar_id', 'academic_calendar_event_id',
        'title', 'start_date', 'end_date', 'description', 'reason', 'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function collegeCalendar(): BelongsTo
    {
        return $this->belongsTo(CollegeAcademicCalendar::class, 'college_academic_calendar_id');
    }

    public function universityEvent(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendarEvent::class, 'academic_calendar_event_id');
    }
}
