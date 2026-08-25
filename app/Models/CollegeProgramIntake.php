<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollegeProgramIntake extends Model
{
    protected $fillable = [
        'college_program_offering_id',
        'approved_capacity',
        'allocation_mode',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'approved_capacity' => 'integer',
    ];

    public function offering(): BelongsTo
    {
        return $this->belongsTo(
            CollegeProgramOffering::class,
            'college_program_offering_id'
        );
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(
            CollegeProgramIntakeAllocation::class,
            'college_program_intake_id'
        )->orderBy('display_order')->orderBy('id');
    }
}
