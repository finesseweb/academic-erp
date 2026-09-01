<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeAdmissionFormFieldComparison extends Model
{
    protected $fillable = ['target_field_id','source_field_id','operator','is_active'];
    protected $casts = ['is_active'=>'boolean'];
    public function targetField(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormField::class, 'target_field_id'); }
    public function sourceField(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormField::class, 'source_field_id'); }
}
