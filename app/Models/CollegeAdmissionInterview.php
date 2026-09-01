<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo,HasMany};
class CollegeAdmissionInterview extends Model {
 protected $fillable=['college_admission_application_id','college_admission_application_choice_id','college_admission_selection_rule_id','panel_name','scheduled_at','venue','status','final_raw_score','final_max_score','normalized_score','remarks','evaluated_at','created_by','updated_by'];
 protected $casts=['scheduled_at'=>'datetime','evaluated_at'=>'datetime','final_raw_score'=>'decimal:3','final_max_score'=>'decimal:3','normalized_score'=>'decimal:3'];
 public function application():BelongsTo{return $this->belongsTo(CollegeAdmissionApplication::class,'college_admission_application_id');}
 public function choice():BelongsTo{return $this->belongsTo(CollegeAdmissionApplicationChoice::class,'college_admission_application_choice_id');}
 public function selectionRule():BelongsTo{return $this->belongsTo(CollegeAdmissionSelectionRule::class,'college_admission_selection_rule_id');}
 public function evaluators():HasMany{return $this->hasMany(CollegeAdmissionInterviewEvaluator::class,'college_admission_interview_id');}
}
