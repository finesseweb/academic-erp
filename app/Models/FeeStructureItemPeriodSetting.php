<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructureItemPeriodSetting extends Model
{
    protected $fillable = [
        'fee_structure_item_id',
        'period_no',
        'is_mandatory',
        'is_enrollment_clearance_required',
        'installment_allowed',
        'display_order',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'period_no' => 'integer',
        'is_mandatory' => 'boolean',
        'is_enrollment_clearance_required' => 'boolean',
        'installment_allowed' => 'boolean',
        'display_order' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(FeeStructureItem::class, 'fee_structure_item_id');
    }
}
