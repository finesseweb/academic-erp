<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class FeePaymentRefund extends Model {
    protected $fillable=['university_id','college_id','admission_id','academic_session_id','fee_payment_id','refund_no','refund_date','amount','refund_mode','reference_no','reason','status','refunded_by'];
    protected $casts=['refund_date'=>'date','amount'=>'decimal:2'];
    public function allocations(): HasMany { return $this->hasMany(FeePaymentRefundAllocation::class); }
}
