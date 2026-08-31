<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ApplicantProfile extends Model {
 protected $fillable=['user_id','college_id','date_of_birth','phone','lifecycle_status','student_id','student_enabled_at'];
 protected $casts=['date_of_birth'=>'date','student_enabled_at'=>'datetime'];
 public function user(): BelongsTo { return $this->belongsTo(User::class); }
 public function college(): BelongsTo { return $this->belongsTo(College::class); }
}
