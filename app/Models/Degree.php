<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Degree extends Model
{
    protected $fillable = ['university_id', 'degree_level_id', 'name', 'code', 'description', 'typical_duration_years', 'display_order', 'status'];

    public function degreeLevel(): BelongsTo
    {
        return $this->belongsTo(DegreeLevel::class);
    }
}
