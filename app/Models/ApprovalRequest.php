<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApprovalRequest extends Model
{
    protected $fillable = [
        'approval_workflow_id',
        'university_id',
        'subject_type',
        'subject_id',
        'status',
        'current_stage_sequence',
        'submitted_by',
        'submitted_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(
            ApprovalWorkflow::class,
            'approval_workflow_id'
        );
    }

    public function stages(): HasMany
    {
        return $this->hasMany(ApprovalRequestStage::class)
            ->orderBy('sequence_no');
    }
}
