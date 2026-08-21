<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseType extends Model
{
    protected $fillable = [
        'university_id',
        'name',
        'code',
        'description',
        'display_order',
        'status',
    ];
}
