<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeePayment extends Model
{
    protected $fillable = [
        'university_id','college_id','admission_id','academic_session_id','receipt_no','payment_date',
        'amount','currency','payment_mode','reference_no','status','notes','collected_by',
        'reversed_at','reversed_by','reversal_reason',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'reversed_at' => 'datetime',
    ];

    public function allocations(): HasMany { return $this->hasMany(FeePaymentAllocation::class); }
    public function admission(): BelongsTo { return $this->belongsTo(Admission::class); }
    public function college(): BelongsTo { return $this->belongsTo(College::class); }
}
