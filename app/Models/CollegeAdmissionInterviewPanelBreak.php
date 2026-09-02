<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CollegeAdmissionInterviewPanelBreak extends Model {
 protected $fillable=['college_admission_interview_panel_id','label','starts_at','ends_at'];
 protected $casts=['starts_at'=>'datetime','ends_at'=>'datetime'];
 public function panel():BelongsTo{return $this->belongsTo(CollegeAdmissionInterviewPanel::class,'college_admission_interview_panel_id');}
}
