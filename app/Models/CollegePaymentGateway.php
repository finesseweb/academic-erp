<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class CollegePaymentGateway extends Model {
 protected $fillable=['university_id','college_id','provider','display_name','environment','merchant_id','key_id','key_secret','webhook_secret','provider_config','status','created_by','updated_by'];
 protected $hidden=['key_secret','webhook_secret','provider_config'];
 protected $casts=['key_secret'=>'encrypted','webhook_secret'=>'encrypted','provider_config'=>'encrypted:array'];
 public function college(): BelongsTo{return $this->belongsTo(College::class);}
 public function mappings(): HasMany{return $this->hasMany(FeeHeadGatewayMapping::class);}
}
