<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicCalendarEvent extends Model
{
    protected $fillable = [
        'academic_calendar_id', 'academic_calendar_term_period_id', 'event_type', 'title', 'start_date', 'end_date',
        'description', 'allow_college_override', 'status', 'display_order',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'allow_college_override' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function termPeriod(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendarTermPeriod::class, 'academic_calendar_term_period_id');
    }

    public function academicCalendar(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendar::class);
    }
}
