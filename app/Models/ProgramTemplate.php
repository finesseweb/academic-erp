<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramTemplate extends Model
{
    protected $fillable = ['university_id', 'degree_id', 'discipline_id', 'name', 'code', 'term_structure', 'duration_terms', 'description', 'display_order', 'status'];

    public function degree(): BelongsTo
    {
        return $this->belongsTo(Degree::class);
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(AcademicDiscipline::class, 'discipline_id');
    }
}
