<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CurriculumSlot extends Model
{
    protected $fillable = [
        'curriculum_term_id',
        'course_category_id',
        'course_type_id',
        'credits',
        'name',
        'display_order',
        'selection_mode',
        'min_selection',
        'max_selection',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'credits' => 'decimal:2',
            'min_selection' => 'integer',
            'max_selection' => 'integer',
        ];
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(CurriculumTerm::class, 'curriculum_term_id');
    }

    public function courseMappings(): HasMany
    {
        return $this->hasMany(
            CurriculumCourseMapping::class,
            'curriculum_slot_id'
        );
    }
}
