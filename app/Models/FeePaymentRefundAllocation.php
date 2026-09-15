<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FeePaymentRefundAllocation extends Model {
    protected $fillable=['fee_payment_refund_id','fee_payment_allocation_id','fee_demand_id','fee_demand_item_id','fee_installment_schedule_id','fee_late_fine_charge_id','amount','sequence_no'];
    protected $casts=['amount'=>'decimal:2','sequence_no'=>'integer'];
}
