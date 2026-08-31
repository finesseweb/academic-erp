<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CollegeApplicantRegistrationSetting extends Model {
 protected $fillable=['college_id','registration_enabled','email_verification_required','captcha_required','updated_by'];
 protected $casts=['registration_enabled'=>'boolean','email_verification_required'=>'boolean','captcha_required'=>'boolean'];
 public static function forCollege(int $collegeId): self { return static::firstOrCreate(['college_id'=>$collegeId],['registration_enabled'=>true,'email_verification_required'=>false,'captcha_required'=>false]); }
}
