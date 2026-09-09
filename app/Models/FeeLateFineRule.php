<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeLateFineRule extends Model
{
    protected $fillable = [
        'university_id','college_id','academic_session_id','college_program_offering_id','fee_head_id',
        'name','code','source_type','calculation_type','frequency','value','grace_days','maximum_fine_amount',
        'status','notes','created_by','updated_by',
    ];
    protected $casts = ['value'=>'decimal:4','grace_days'=>'integer','maximum_fine_amount'=>'decimal:2'];
    public function offering(): BelongsTo { return $this->belongsTo(CollegeProgramOffering::class, 'college_program_offering_id'); }
    public function feeHead(): BelongsTo { return $this->belongsTo(FeeHead::class, 'fee_head_id'); }
    public function academicSession(): BelongsTo { return $this->belongsTo(AcademicSession::class, 'academic_session_id'); }
    public function charges(): HasMany { return $this->hasMany(FeeLateFineCharge::class, 'fee_late_fine_rule_id'); }
}
