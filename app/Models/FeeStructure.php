<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model
{
    protected $fillable = ['university_id','college_id','college_program_offering_id','program_template_id','curriculum_id','academic_session_id','name','code','purpose','charge_basis','charge_period_no','college_applicability','currency','status','notes','created_by','updated_by'];

    public function university(): BelongsTo { return $this->belongsTo(University::class); }
    public function college(): BelongsTo { return $this->belongsTo(College::class); }
    public function offering(): BelongsTo { return $this->belongsTo(CollegeProgramOffering::class, 'college_program_offering_id'); }
    public function programTemplate(): BelongsTo { return $this->belongsTo(ProgramTemplate::class); }
    public function curriculum(): BelongsTo { return $this->belongsTo(Curriculum::class); }
    public function academicSession(): BelongsTo { return $this->belongsTo(AcademicSession::class); }
    public function items(): HasMany { return $this->hasMany(FeeStructureItem::class)->orderBy('display_order')->orderBy('id'); }
    public function collegeAdoptions(): HasMany { return $this->hasMany(CollegeFeeStructureAdoption::class, 'university_fee_structure_id'); }
}
