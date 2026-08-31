<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curriculum extends Model
{
    protected $fillable = [
        'parent_curriculum_id',
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
        'revision_type',
        'revision_reason',
        'revision_effective_from',
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
            'revision_effective_from' => 'date',
        ];
    }



    public function scopeCurrentApproved($query)
    {
        return $query
            ->where('lifecycle_status', 'ACTIVE')
            ->where('approval_status', 'APPROVED')
            ->whereDoesntHave('amendments', fn ($q) => $q->where('approval_status', 'APPROVED'));
    }

    public function parentCurriculum(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_curriculum_id');
    }

    public function amendments(): HasMany
    {
        return $this->hasMany(self::class, 'parent_curriculum_id');
    }

    public function hasApprovedSuccessor(): bool
    {
        return $this->amendments()
            ->where('approval_status', 'APPROVED')
            ->exists();
    }

    public function isCurrentApprovedVersion(): bool
    {
        return $this->lifecycle_status === 'ACTIVE'
            && ($this->approval_status ?? 'NOT_SUBMITTED') === 'APPROVED'
            && ! $this->hasApprovedSuccessor();
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
