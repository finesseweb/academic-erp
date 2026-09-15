<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FeeAdjustment extends Model {
    protected $fillable=['university_id','college_id','admission_id','academic_session_id','fee_demand_id','fee_demand_item_id','adjustment_no','adjustment_date','direction','amount','reason_code','reason','status','posted_by','reversed_at','reversed_by','reversal_reason'];
    protected $casts=['adjustment_date'=>'date','amount'=>'decimal:2','reversed_at'=>'datetime'];
}
