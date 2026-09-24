<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacultyAllocation extends Model
{
    protected $fillable = ['course_offering_id', 'section_id', 'faculty_user_id', 'teaching_role', 'weekly_load', 'status', 'notes', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['weekly_load' => 'decimal:2'];
    }

    /** @return BelongsTo<CourseOffering, $this> */
    public function courseOffering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function faculty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'faculty_user_id');
    }
}
