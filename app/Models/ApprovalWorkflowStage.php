<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalWorkflowStage extends Model
{
    protected $fillable = [
        'approval_workflow_id', 'sequence_no', 'name', 'approver_role_id',
        'remarks_required_on_reject', 'remarks_required_on_return',
        'status', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'remarks_required_on_reject' => 'boolean',
            'remarks_required_on_return' => 'boolean',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ApprovalWorkflow::class, 'approval_workflow_id');
    }

    public function approverRole(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'approver_role_id');
    }
}
