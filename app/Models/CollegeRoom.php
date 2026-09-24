<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeRoom extends Model
{
    protected $fillable = ['college_id', 'code', 'name', 'building', 'floor', 'room_type', 'capacity', 'status', 'notes', 'created_by', 'updated_by'];

    /** @return BelongsTo<College, $this> */
    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }
}
