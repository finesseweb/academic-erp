<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CollegeApplicantRegistrationSetting extends Model {
 protected $fillable=['college_id','registration_enabled','email_verification_required','captcha_required','registration_number_format','registration_sequence_next','application_help_text','updated_by'];
 protected $casts=['registration_enabled'=>'boolean','email_verification_required'=>'boolean','captcha_required'=>'boolean','registration_sequence_next'=>'integer'];
 public static function forCollege(int $collegeId): self { return static::firstOrCreate(['college_id'=>$collegeId],['registration_enabled'=>true,'email_verification_required'=>false,'captcha_required'=>false,'registration_number_format'=>'{COLLEGE_CODE}/{YEAR}/{SEQ:6}','registration_sequence_next'=>1,'application_help_text'=>'For help with this application, please contact the college admission office.']); }
}
