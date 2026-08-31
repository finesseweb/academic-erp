<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollegeAdmissionFormStep extends Model
{
    protected $fillable = ['college_admission_form_template_id','title','code','description','display_order','is_locked','status'];
    protected $casts = ['is_locked' => 'boolean'];
    public function template(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormTemplate::class, 'college_admission_form_template_id'); }
    public function panels(): HasMany { return $this->hasMany(CollegeAdmissionFormPanel::class)->orderBy('display_order')->orderBy('id'); }
    public function fields(): HasMany { return $this->hasMany(CollegeAdmissionFormField::class)->orderBy('display_order')->orderBy('id'); }
}
