<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeProgramReservationAllocation extends Model
{
    protected $fillable = [
        'college_program_reservation_plan_id','reservation_category_id',
        'seat_capacity','display_order','status','created_by','updated_by',
    ];

    protected $casts = ['seat_capacity'=>'integer','display_order'=>'integer'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramReservationPlan::class, 'college_program_reservation_plan_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReservationCategory::class, 'reservation_category_id');
    }
}
