<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CollegeAdmissionFormField extends Model
{
    protected $fillable = ['college_admission_form_step_id','college_admission_form_panel_id','field_key','label','field_type','placeholder','help_text','is_required','is_locked','display_order','validation_rules','visibility_rules','condition_match_mode','status'];
    protected $casts = ['is_required'=>'boolean','is_locked'=>'boolean','validation_rules'=>'array','visibility_rules'=>'array'];
    public function panel(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormPanel::class, 'college_admission_form_panel_id'); }
    public function step(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormStep::class, 'college_admission_form_step_id'); }
    public function options(): HasMany { return $this->hasMany(CollegeAdmissionFormFieldOption::class)->orderBy('display_order')->orderBy('id'); }
    public function conditions(): HasMany { return $this->hasMany(CollegeAdmissionFormFieldCondition::class)->orderBy('display_order')->orderBy('id'); }
    public function scopes(): HasMany { return $this->hasMany(CollegeAdmissionFormFieldScope::class)->orderBy('id'); }
    public function comparisonRule(): HasOne { return $this->hasOne(CollegeAdmissionFormFieldComparison::class, 'target_field_id'); }
    public function copyRule(): HasOne { return $this->hasOne(CollegeAdmissionFormFieldCopyRule::class, 'target_field_id'); }
}
