<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStudentBenefit extends Model
{
    protected $fillable = [
        'university_id','college_id','admission_id','fee_demand_id','fee_scholarship_scheme_id',
        'application_mode','status','eligible_base_amount','calculated_benefit_amount','sanctioned_amount',
        'eligibility_snapshot','scheme_name_snapshot','scheme_code_snapshot','benefit_type_snapshot',
        'calculation_type_snapshot','benefit_value_snapshot','maximum_benefit_amount_snapshot',
        'application_note','decision_note','applied_at','applied_by','decided_at','decided_by',
        'cancelled_at','cancelled_by','cancellation_reason',
    ];

    protected $casts = [
        'eligible_base_amount' => 'decimal:2',
        'calculated_benefit_amount' => 'decimal:2',
        'sanctioned_amount' => 'decimal:2',
        'benefit_value_snapshot' => 'decimal:2',
        'maximum_benefit_amount_snapshot' => 'decimal:2',
        'eligibility_snapshot' => 'array',
        'applied_at' => 'datetime',
        'decided_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function demand(): BelongsTo { return $this->belongsTo(FeeDemand::class, 'fee_demand_id'); }
    public function scheme(): BelongsTo { return $this->belongsTo(FeeScholarshipScheme::class, 'fee_scholarship_scheme_id'); }
    public function items(): HasMany { return $this->hasMany(FeeStudentBenefitItem::class, 'fee_student_benefit_id')->orderBy('id'); }
}
