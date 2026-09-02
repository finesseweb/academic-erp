<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class CollegeAdmissionInterviewPanel extends Model {
 protected $fillable=['college_id','name','starts_at','slot_duration_minutes','session_duration_minutes','venue','status','created_by','updated_by'];
 protected $casts=['starts_at'=>'datetime','slot_duration_minutes'=>'integer','session_duration_minutes'=>'integer'];
 public function college():BelongsTo{return $this->belongsTo(College::class);}
 public function evaluators():HasMany{return $this->hasMany(CollegeAdmissionInterviewPanelEvaluator::class,'college_admission_interview_panel_id');}
 public function interviews():HasMany{return $this->hasMany(CollegeAdmissionInterview::class,'college_admission_interview_panel_id');}
 public function breakPeriods():HasMany{return $this->hasMany(CollegeAdmissionInterviewPanelBreak::class,'college_admission_interview_panel_id')->orderBy('starts_at');}
}
