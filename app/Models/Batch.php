<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $fillable = [
        'college_program_offering_id',
        'code',
        'name',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    public function offering(): BelongsTo
    {
        return $this->belongsTo(
            CollegeProgramOffering::class,
            'college_program_offering_id'
        );
    }
}
