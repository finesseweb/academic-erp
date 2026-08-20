<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    protected $fillable = ['university_id', 'name', 'code', 'starts_on', 'ends_on', 'status', 'is_current'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'is_current' => 'boolean'];
    }
}
