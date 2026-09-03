<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class CollegeAdmissionApplicationFieldValue extends Model
{
    protected $fillable=['college_admission_application_id','college_admission_form_field_id','value_text','value_json','file_path','file_name','file_mime','file_size'];
    protected $casts=['value_json'=>'array'];
    public function application(): BelongsTo { return $this->belongsTo(CollegeAdmissionApplication::class, 'college_admission_application_id'); }
    public function field(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormField::class, 'college_admission_form_field_id'); }
}
