<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStudentBenefitItem extends Model
{
    protected $fillable = [
        'fee_student_benefit_id','fee_demand_item_id','fee_head_id','eligible_amount','calculated_amount','sanctioned_amount',
    ];

    protected $casts = [
        'eligible_amount' => 'decimal:2',
        'calculated_amount' => 'decimal:2',
        'sanctioned_amount' => 'decimal:2',
    ];

    public function benefit(): BelongsTo { return $this->belongsTo(FeeStudentBenefit::class, 'fee_student_benefit_id'); }
    public function demandItem(): BelongsTo { return $this->belongsTo(FeeDemandItem::class, 'fee_demand_item_id'); }
}
