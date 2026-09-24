<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfileValue extends Model
{
    protected $fillable = [
        'student_id', 'source_application_field_id', 'profile_key', 'label_snapshot',
        'value_text', 'value_json', 'file_path', 'file_name', 'file_mime', 'file_size',
    ];

    protected $casts = ['value_json' => 'array'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function sourceField(): BelongsTo { return $this->belongsTo(CollegeAdmissionFormField::class, 'source_application_field_id'); }
}
