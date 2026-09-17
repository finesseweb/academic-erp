<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class StudentIdentitySetting extends Model
{
    protected $fillable=['college_id','student_uid_format','university_roll_format','class_roll_format','class_roll_scope','updated_by'];
    public static function forCollege(int $collegeId): self
    {
        return static::firstOrCreate(['college_id'=>$collegeId],[
            'student_uid_format'=>'{COLLEGE_CODE}/STU/{YEAR}/{SEQ:6}',
            'university_roll_format'=>'{YEAR}/{PROGRAM_CODE}/{SEQ:6}',
            'class_roll_format'=>'{SEQ:3}',
            'class_roll_scope'=>'PROGRAMME_OFFERING',
        ]);
    }
}
