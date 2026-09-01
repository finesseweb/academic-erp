<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeAdmissionFormFieldCondition extends Model
{
    protected $fillable = ['college_admission_form_field_id','source_field_id','operator','compare_values','display_order','is_active'];
    protected $casts = ['compare_values'=>'array','is_active'=>'boolean'];
    public function field(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormField::class, 'college_admission_form_field_id'); }
    public function sourceField(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormField::class, 'source_field_id'); }
}
