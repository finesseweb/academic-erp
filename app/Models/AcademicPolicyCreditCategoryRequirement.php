<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicPolicyCreditCategoryRequirement extends Model
{
    protected $fillable = [
        'academic_policy_id',
        'course_category_id',
        'minimum_credits',
        'maximum_credits',
        'display_order',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'minimum_credits' => 'decimal:2',
            'maximum_credits' => 'decimal:2',
            'display_order' => 'integer',
        ];
    }

    public function academicPolicy(): BelongsTo
    {
        return $this->belongsTo(AcademicPolicy::class);
    }

    public function courseCategory(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class);
    }
}
