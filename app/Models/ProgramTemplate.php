<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramTemplate extends Model
{
    protected $fillable = [
        'university_id',
        'degree_id',
        'name',
        'code',
        'term_structure',
        'duration_terms',
        'description',
        'display_order',
        'status',
    ];

    public function degree(): BelongsTo
    {
        return $this->belongsTo(Degree::class);
    }

    public function disciplineMappings(): HasMany
    {
        return $this->hasMany(ProgramTemplateDiscipline::class);
    }

    public function disciplines(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademicDiscipline::class,
            'program_template_disciplines',
            'program_template_id',
            'discipline_id'
        )->withTimestamps();
    }
}
