<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlinePaymentTransaction extends Model
{
    protected $fillable = [
        'university_id', 'college_id', 'college_payment_gateway_id', 'provider',
        'environment', 'purpose', 'amount', 'currency', 'provider_order_id',
        'provider_payment_id', 'provider_status', 'status', 'reference_no',
        'request_context', 'response_context', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'request_context' => 'array',
        'response_context' => 'array',
    ];

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(CollegePaymentGateway::class, 'college_payment_gateway_id');
    }
}
