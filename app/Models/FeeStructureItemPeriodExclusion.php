<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructureItemPeriodExclusion extends Model
{
    protected $fillable = [
        'fee_structure_item_id',
        'period_no',
        'created_by',
    ];

    protected $casts = [
        'period_no' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(FeeStructureItem::class, 'fee_structure_item_id');
    }
}
