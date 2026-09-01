<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CollegeAdmissionInterviewEvaluator extends Model {
 protected $fillable=['college_admission_interview_id','evaluator_user_id','evaluator_name_snapshot','raw_score','max_score','normalized_score','remarks'];
 protected $casts=['raw_score'=>'decimal:3','max_score'=>'decimal:3','normalized_score'=>'decimal:3'];
 public function interview():BelongsTo{return $this->belongsTo(CollegeAdmissionInterview::class,'college_admission_interview_id');}
 public function evaluator():BelongsTo{return $this->belongsTo(User::class,'evaluator_user_id');}
}
