<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollegeAdmissionFormPanel extends Model
{
    protected $fillable = ['college_admission_form_step_id','title','code','description','display_order','is_locked','status'];
    protected $casts = ['is_locked'=>'boolean'];
    public function step(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormStep::class, 'college_admission_form_step_id'); }
    public function fields(): HasMany { return $this->hasMany(CollegeAdmissionFormField::class, 'college_admission_form_panel_id')->orderBy('display_order')->orderBy('id'); }
}
