<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollegeProgramIntakeAllocation extends Model
{
    protected $fillable = [
        'college_program_intake_id',
        'parent_allocation_id',
        'discipline_id',
        'specialization_id',
        'seat_scope_type',
        'seat_capacity',
        'display_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'seat_capacity' => 'integer',
        'display_order' => 'integer',
    ];

    public function intake(): BelongsTo
    {
        return $this->belongsTo(
            CollegeProgramIntake::class,
            'college_program_intake_id'
        );
    }

    public function discipline(): BelongsTo
    {
        return $this->belongsTo(
            AcademicDiscipline::class,
            'discipline_id'
        );
    }

    public function specialization(): BelongsTo
    {
        return $this->belongsTo(
            AcademicDiscipline::class,
            'specialization_id'
        );
    }

    public function parentAllocation(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'parent_allocation_id'
        );
    }

    public function childAllocations(): HasMany
    {
        return $this->hasMany(
            self::class,
            'parent_allocation_id'
        )->orderBy('display_order')->orderBy('id');
    }
}
