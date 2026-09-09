<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeLateFineCharge extends Model
{
    protected $fillable = [
        'fee_late_fine_rule_id','university_id','college_id','fee_demand_id','fee_demand_item_id','fee_installment_schedule_id',
        'due_date','calculated_as_of','overdue_days','base_outstanding_amount','fine_amount','status','superseded_by_id',
        'calculated_by','superseded_at',
    ];
    protected $casts = [
        'due_date'=>'date','calculated_as_of'=>'date','overdue_days'=>'integer','base_outstanding_amount'=>'decimal:2',
        'fine_amount'=>'decimal:2','superseded_at'=>'datetime',
    ];
    public function rule(): BelongsTo { return $this->belongsTo(FeeLateFineRule::class, 'fee_late_fine_rule_id'); }
    public function demand(): BelongsTo { return $this->belongsTo(FeeDemand::class, 'fee_demand_id'); }
    public function demandItem(): BelongsTo { return $this->belongsTo(FeeDemandItem::class, 'fee_demand_item_id'); }
    public function installment(): BelongsTo { return $this->belongsTo(FeeInstallmentSchedule::class, 'fee_installment_schedule_id'); }
}
