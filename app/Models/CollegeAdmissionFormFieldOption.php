<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CollegeAdmissionFormFieldOption extends Model
{
    protected $fillable=['college_admission_form_field_id','value','label','display_order','is_active'];
    protected $casts=['is_active'=>'boolean'];
    public function field(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormField::class, 'college_admission_form_field_id'); }
}
