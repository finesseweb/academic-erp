<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeCategory extends Model
{
    protected $fillable = [
        'university_id','college_id','name','code','description','display_order','status','created_by','updated_by',
    ];

    public function university(): BelongsTo { return $this->belongsTo(University::class); }
    public function college(): BelongsTo { return $this->belongsTo(College::class); }
    public function heads(): HasMany { return $this->hasMany(FeeHead::class); }
}
