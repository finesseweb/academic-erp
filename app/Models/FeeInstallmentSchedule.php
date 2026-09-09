<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FeeInstallmentSchedule extends Model {
    protected $fillable=['fee_demand_id','fee_demand_item_id','installment_no','amount','paid_amount','allocation_percentage','source_mode','due_date','status','created_by','cancelled_by','cancelled_at','cancellation_reason'];
    protected $casts=['installment_no'=>'integer','amount'=>'decimal:2','paid_amount'=>'decimal:2','allocation_percentage'=>'decimal:4','due_date'=>'date','cancelled_at'=>'datetime'];
    public function demand():BelongsTo{return $this->belongsTo(FeeDemand::class,'fee_demand_id');}
    public function demandItem():BelongsTo{return $this->belongsTo(FeeDemandItem::class,'fee_demand_item_id');}
}
