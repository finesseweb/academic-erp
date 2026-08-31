<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CurriculumCourseMapping extends Model
{
    protected $fillable = [
        'curriculum_slot_id',
        'course_id',
        'discipline_id',
        'specialization_id',
        'source_discipline_id',
        'display_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(CurriculumSlot::class, 'curriculum_slot_id');
    }
}
