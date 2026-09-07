<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CollegeAdmissionFormMapping extends Model
{
    protected $casts=['public_enabled'=>'boolean','public_enabled_at'=>'datetime','seat_selection_required'=>'boolean'];
    protected $fillable=['college_admission_form_template_id','university_id','college_id','degree_level_id','degree_id','program_template_id','college_program_offering_id','college_admission_cycle_id','status','public_enabled','public_slug','public_enabled_at','seat_selection_required','reservation_category_field_id'];
    public function template(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormTemplate::class, 'college_admission_form_template_id'); }
}
