<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProgramTemplateDiscipline extends Model
{
    protected $fillable = [
        'program_template_id',
        'discipline_id',
        'specialization_required',
    ];

    public function programTemplate(): BelongsTo
    {
        return $this->belongsTo(ProgramTemplate::class);
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(AcademicDiscipline::class, 'discipline_id');
    }

    public function specializations(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademicDiscipline::class,
            'program_template_discipline_specializations',
            'program_template_discipline_id',
            'specialization_id'
        )->withTimestamps();
    }
}
