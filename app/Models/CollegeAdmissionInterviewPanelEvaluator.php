<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CollegeAdmissionInterviewPanelEvaluator extends Model {
 protected $fillable=['college_admission_interview_panel_id','evaluator_user_id','evaluator_name_snapshot'];
 public function panel():BelongsTo{return $this->belongsTo(CollegeAdmissionInterviewPanel::class,'college_admission_interview_panel_id');}
 public function evaluator():BelongsTo{return $this->belongsTo(User::class,'evaluator_user_id');}
}
