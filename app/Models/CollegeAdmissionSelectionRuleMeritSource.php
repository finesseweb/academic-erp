<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeAdmissionSelectionRuleMeritSource extends Model
{
    protected $fillable = [
        'college_admission_selection_rule_id','label','source_type','obtained_field_id','maximum_field_id',
        'weight_percent','display_order',
    ];

    protected $casts = ['weight_percent'=>'decimal:2','display_order'=>'integer'];

    public function rule(): BelongsTo { return $this->belongsTo(CollegeAdmissionSelectionRule::class, 'college_admission_selection_rule_id'); }
    public function obtainedField(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormField::class, 'obtained_field_id'); }
    public function maximumField(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormField::class, 'maximum_field_id'); }
}
