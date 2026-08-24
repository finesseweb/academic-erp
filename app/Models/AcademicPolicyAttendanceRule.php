<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicPolicyAttendanceRule extends Model
{
    protected $fillable = [
        'academic_policy_id',
        'minimum_attendance_percent',
        'calculation_level',
        'allow_condonation',
        'condonation_minimum_percent',
        'maximum_condonable_shortage_percent',
        'attendance_required_for_exam',
        'allow_special_exemption',
        'rounding_rule',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'minimum_attendance_percent' => 'decimal:2',
            'allow_condonation' => 'boolean',
            'condonation_minimum_percent' => 'decimal:2',
            'maximum_condonable_shortage_percent' => 'decimal:2',
            'attendance_required_for_exam' => 'boolean',
            'allow_special_exemption' => 'boolean',
        ];
    }

    public function academicPolicy(): BelongsTo
    {
        return $this->belongsTo(AcademicPolicy::class);
    }
}
