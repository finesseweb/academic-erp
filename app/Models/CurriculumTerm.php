<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumTerm extends Model
{
    protected $fillable = [
        'curriculum_id',
        'sequence_no',
        'name',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
        ];
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function calendarPeriods(): HasMany
    {
        return $this->hasMany(AcademicCalendarTermPeriod::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(CurriculumSlot::class, 'curriculum_term_id');
    }

}
