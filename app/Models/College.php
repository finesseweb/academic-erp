<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class College extends Model
{
    protected $fillable = [
        'university_id', 'name', 'code', 'affiliation_type', 'status', 'official_email',
        'official_phone', 'website', 'address_line_1', 'address_line_2', 'city', 'state',
        'postal_code', 'country', 'timezone', 'principal_user_id',
    ];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function principal(): BelongsTo
    {
        return $this->belongsTo(User::class, 'principal_user_id');
    }

    public function programOfferings(): HasMany
    {
        return $this->hasMany(CollegeProgramOffering::class);
    }

    public function collegeAcademicCalendars(): HasMany
    {
        return $this->hasMany(CollegeAcademicCalendar::class);
    }
}
