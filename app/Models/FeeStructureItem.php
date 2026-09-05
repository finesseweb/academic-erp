<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructureItem extends Model
{
    protected $fillable = ['fee_structure_id','fee_head_id','amount','is_mandatory','is_enrollment_clearance_required','installment_allowed','display_order','status','created_by','updated_by'];
    protected $casts = ['amount'=>'decimal:2','is_mandatory'=>'boolean','is_enrollment_clearance_required'=>'boolean','installment_allowed'=>'boolean'];
    public function structure(): BelongsTo { return $this->belongsTo(FeeStructure::class, 'fee_structure_id'); }
    public function head(): BelongsTo { return $this->belongsTo(FeeHead::class, 'fee_head_id'); }
    public function periodAmounts(): HasMany { return $this->hasMany(FeeStructureItemPeriodAmount::class)->orderBy('period_no'); }
    public function periodExclusions(): HasMany { return $this->hasMany(FeeStructureItemPeriodExclusion::class)->orderBy('period_no'); }
    public function periodSettings(): HasMany { return $this->hasMany(FeeStructureItemPeriodSetting::class)->orderBy('period_no'); }
}
