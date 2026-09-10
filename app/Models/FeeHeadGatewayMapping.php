<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FeeHeadGatewayMapping extends Model {
 protected $fillable=['college_payment_gateway_id','fee_head_id','product_code','settlement_code','status','created_by','updated_by'];
 public function gateway(): BelongsTo{return $this->belongsTo(CollegePaymentGateway::class,'college_payment_gateway_id');}
 public function feeHead(): BelongsTo{return $this->belongsTo(FeeHead::class);}
}
