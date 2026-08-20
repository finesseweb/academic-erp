<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicDiscipline extends Model
{
    protected $fillable = ['university_id', 'parent_id', 'kind', 'name', 'code', 'description', 'display_order', 'status'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
