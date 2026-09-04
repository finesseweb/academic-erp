<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructureItemPeriodAmount extends Model
{
    protected $fillable = [
        'fee_structure_item_id',
        'period_no',
        'amount',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'period_no' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(FeeStructureItem::class, 'fee_structure_item_id');
    }
}
