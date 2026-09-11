<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlinePaymentTransaction extends Model
{
    protected $fillable = [
        'university_id', 'college_id', 'college_payment_gateway_id', 'fee_demand_id', 'admission_id', 'fee_payment_id', 'provider',
        'environment', 'purpose', 'amount', 'currency', 'provider_order_id',
        'provider_payment_id', 'provider_status', 'status', 'reference_no',
        'request_context', 'response_context', 'verified_at', 'posted_at', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_context' => 'array',
        'response_context' => 'array',
        'verified_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function feeDemand(): BelongsTo
    {
        return $this->belongsTo(FeeDemand::class);
    }

    public function feePayment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class);
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(CollegePaymentGateway::class, 'college_payment_gateway_id');
    }
}
