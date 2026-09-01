<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeAdmissionFormFieldCopyRule extends Model
{
    protected $fillable = ['target_field_id','source_field_id','trigger_field_id','trigger_values','is_read_only_when_active','is_active'];
    protected $casts = ['trigger_values'=>'array','is_read_only_when_active'=>'boolean','is_active'=>'boolean'];
    public function targetField(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormField::class, 'target_field_id'); }
    public function sourceField(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormField::class, 'source_field_id'); }
    public function triggerField(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormField::class, 'trigger_field_id'); }
}
