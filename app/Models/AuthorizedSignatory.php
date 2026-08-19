<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuthorizedSignatory extends Model
{
    protected $fillable = ['university_id', 'full_name', 'designation', 'authority_type', 'email', 'phone', 'effective_from', 'effective_until', 'status', 'notes'];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_until' => 'date'];
    }

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }
}
