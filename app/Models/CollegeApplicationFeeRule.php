<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CollegeApplicationFeeRule extends Model
{
    protected $fillable=['university_id','college_id','degree_level_id','degree_id','program_template_id','college_program_offering_id','college_admission_cycle_id','name','fee_required','amount','currency','status','created_by','updated_by'];
    protected $casts=['fee_required'=>'boolean','amount'=>'decimal:2'];
}
