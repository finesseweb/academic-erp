<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
    protected $fillable = ['university_id', 'name', 'code', 'category_group', 'description', 'display_order', 'status'];
}
