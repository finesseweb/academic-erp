<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalRequestStage extends Model
{
    protected $fillable = [
        'approval_request_id',
        'sequence_no',
        'name',
        'approver_role_id',
        'status',
        'decided_by',
        'remarks',
        'decided_at',
        'remarks_required_on_reject',
        'remarks_required_on_return',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
            'remarks_required_on_reject' => 'boolean',
            'remarks_required_on_return' => 'boolean',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(
            ApprovalRequest::class,
            'approval_request_id'
        );
    }
}
