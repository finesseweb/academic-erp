<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicCalendar extends Model
{
    protected $fillable = [
        'university_id', 'academic_session_id', 'name', 'code', 'notes',
        'status', 'created_by', 'updated_by',
    ];

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(AcademicCalendarEvent::class)->orderBy('start_date')->orderBy('display_order');
    }
}
