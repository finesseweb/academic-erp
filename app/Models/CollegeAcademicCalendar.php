<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollegeAcademicCalendar extends Model
{
    protected $fillable = [
        'college_id', 'university_academic_calendar_id', 'status', 'notes', 'created_by', 'updated_by',
    ];

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function universityCalendar(): BelongsTo
    {
        return $this->belongsTo(AcademicCalendar::class, 'university_academic_calendar_id');
    }

    public function overrides(): HasMany
    {
        return $this->hasMany(CollegeCalendarOverride::class);
    }
}
