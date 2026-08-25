<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationCategory extends Model
{
    protected $fillable = [
        'university_id','name','code','nature','description','display_order',
        'status','created_by','updated_by',
    ];

    protected $casts = ['display_order' => 'integer'];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }
}
