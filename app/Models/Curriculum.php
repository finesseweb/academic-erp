<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curriculum extends Model
{
    protected $fillable = [
        'university_id',
        'program_template_id',
        'academic_session_id',
        'code',
        'name',
        'version',
        'effective_from',
        'effective_to',
        'lifecycle_status',
        'approval_status',
        'structure_validation_hash',
        'structure_validated_at',
        'structure_validated_by',
        'description',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'effective_to' => 'date',
        ];
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function programTemplate(): BelongsTo
    {
        return $this->belongsTo(ProgramTemplate::class);
    }


    public function terms(): HasMany
    {
        return $this->hasMany(CurriculumTerm::class)->orderBy('sequence_no');
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }
}
