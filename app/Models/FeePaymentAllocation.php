<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePaymentAllocation extends Model
{
    protected $fillable = [
        'fee_payment_id','fee_demand_id','fee_demand_item_id','fee_installment_schedule_id',
        'fee_late_fine_charge_id','source_type','due_date','amount','is_mandatory','sequence_no',
    ];

    protected $casts = ['due_date'=>'date','amount'=>'decimal:2','is_mandatory'=>'boolean','sequence_no'=>'integer'];

    public function payment(): BelongsTo { return $this->belongsTo(FeePayment::class, 'fee_payment_id'); }
}
