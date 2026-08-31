<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeAdmissionFormFieldScope extends Model
{
    protected $fillable = ['college_admission_form_field_id','degree_level_id','degree_id','program_template_id','college_program_offering_id','curriculum_id','college_admission_cycle_id','is_active'];
    protected $casts = ['is_active'=>'boolean'];
    public function field(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormField::class, 'college_admission_form_field_id'); }
}
