<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class FeeScholarshipScheme extends Model
{
    protected $fillable = ['university_id','college_id','academic_session_id','program_template_id','college_program_offering_id','name','code','benefit_type','calculation_type','benefit_value','maximum_benefit_amount','eligibility_mode','approval_mode','description','status','created_by','updated_by'];
    protected $casts = ['benefit_value'=>'decimal:2','maximum_benefit_amount'=>'decimal:2'];
    public function academicSession(): BelongsTo { return $this->belongsTo(AcademicSession::class); }
    public function programTemplate(): BelongsTo { return $this->belongsTo(ProgramTemplate::class); }
    public function offering(): BelongsTo { return $this->belongsTo(CollegeProgramOffering::class, 'college_program_offering_id'); }
    public function feeHeads(): BelongsToMany { return $this->belongsToMany(FeeHead::class, 'fee_scholarship_scheme_heads')->withTimestamps(); }
    public function reservationCategories(): BelongsToMany { return $this->belongsToMany(ReservationCategory::class, 'fee_scholarship_scheme_categories')->withTimestamps(); }
}
