<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalWorkflow extends Model
{
    protected $fillable = [
        'university_id', 'name', 'code', 'applies_to', 'description',
        'status', 'created_by', 'updated_by',
    ];

    public function university(): BelongsTo
    {
        return $this->belongsTo(University::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ApprovalWorkflowStage::class)->orderBy('sequence_no');
    }
}
