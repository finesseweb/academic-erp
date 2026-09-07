<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CollegeProgramOffering extends Model
{
    protected $fillable = [
        'college_id',
        'program_template_id',
        'curriculum_id',
        'academic_session_id',
        'status',
        'created_by',
        'updated_by',
    ];

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function programTemplate(): BelongsTo
    {
        return $this->belongsTo(ProgramTemplate::class);
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class, 'college_program_offering_id');
    }

    public function intake(): HasOne
    {
        return $this->hasOne(
            CollegeProgramIntake::class,
            'college_program_offering_id'
        );
    }
}
