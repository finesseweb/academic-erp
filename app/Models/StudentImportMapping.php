<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentImportMapping extends Model
{
    protected $fillable = ['college_id','college_program_offering_id','name','mapping','created_by','updated_by'];
    protected $casts = ['mapping'=>'array'];
}
