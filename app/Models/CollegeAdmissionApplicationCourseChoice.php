<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeAdmissionApplicationCourseChoice extends Model
{
    protected $fillable = [
        'college_admission_application_id', 'curriculum_term_id', 'curriculum_slot_id',
        'curriculum_course_mapping_id', 'course_id', 'selection_source',
    ];

    public function application(): BelongsTo { return $this->belongsTo(CollegeAdmissionApplication::class, 'college_admission_application_id'); }
    public function course(): BelongsTo { return $this->belongsTo(Course::class); }
}
