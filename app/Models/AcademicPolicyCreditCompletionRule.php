<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicPolicyCreditCompletionRule extends Model
{
    protected $fillable = [
        'academic_policy_id',
        'minimum_total_credits',
        'minimum_completion_cgpa',
        'maximum_program_duration_months',
        'allow_credit_transfer',
        'maximum_credit_transfer_percent',
        'allow_credit_exemption',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'minimum_total_credits' => 'decimal:2',
            'minimum_completion_cgpa' => 'decimal:2',
            'maximum_program_duration_months' => 'integer',
            'allow_credit_transfer' => 'boolean',
            'maximum_credit_transfer_percent' => 'decimal:2',
            'allow_credit_exemption' => 'boolean',
        ];
    }

    public function academicPolicy(): BelongsTo
    {
        return $this->belongsTo(AcademicPolicy::class);
    }
}
