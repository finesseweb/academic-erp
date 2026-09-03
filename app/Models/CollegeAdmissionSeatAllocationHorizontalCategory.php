<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeAdmissionSeatAllocationHorizontalCategory extends Model
{
    protected $fillable = [
        'college_admission_seat_allocation_id', 'reservation_category_id',
        'category_code', 'category_name', 'fulfills_target', 'created_by',
    ];

    protected $casts = [
        'fulfills_target' => 'boolean',
    ];

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionSeatAllocation::class, 'college_admission_seat_allocation_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReservationCategory::class, 'reservation_category_id');
    }
}
