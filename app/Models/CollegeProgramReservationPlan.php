<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollegeProgramReservationPlan extends Model
{
    protected $fillable = [
        'college_program_intake_id','bucket_type','bucket_key',
        'discipline_allocation_id','specialization_allocation_id',
        'basis_capacity','status','notes','created_by','updated_by',
    ];

    protected $casts = ['basis_capacity' => 'integer'];

    public function intake(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramIntake::class, 'college_program_intake_id');
    }

    public function disciplineAllocation(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramIntakeAllocation::class, 'discipline_allocation_id');
    }

    public function specializationAllocation(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramIntakeAllocation::class, 'specialization_allocation_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(
            CollegeProgramReservationAllocation::class,
            'college_program_reservation_plan_id'
        )->orderBy('display_order')->orderBy('id');
    }
}
