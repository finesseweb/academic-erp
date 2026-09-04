<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeHead extends Model
{
    protected $fillable = ['university_id','college_id','fee_category_id','name','code','description','is_refundable','status','created_by','updated_by'];
    protected $casts = ['is_refundable' => 'boolean'];

    public function university(): BelongsTo { return $this->belongsTo(University::class); }
    public function college(): BelongsTo { return $this->belongsTo(College::class); }
    public function category(): BelongsTo { return $this->belongsTo(FeeCategory::class, 'fee_category_id'); }
    public function items(): HasMany { return $this->hasMany(FeeStructureItem::class); }
}
