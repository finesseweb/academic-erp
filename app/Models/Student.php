<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'college_id', 'user_id', 'admission_id', 'college_admission_application_id',
        'source_type', 'student_uid', 'university_roll_no', 'full_name', 'date_of_birth', 'email', 'phone',
        'status', 'created_by', 'updated_by',
    ];

    protected $casts = ['date_of_birth' => 'date'];

    public function college(): BelongsTo { return $this->belongsTo(College::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function admission(): BelongsTo { return $this->belongsTo(Admission::class); }
    public function application(): BelongsTo { return $this->belongsTo(CollegeAdmissionApplication::class, 'college_admission_application_id'); }
    public function enrollments(): HasMany { return $this->hasMany(StudentEnrollment::class); }
    public function profileValues(): HasMany { return $this->hasMany(StudentProfileValue::class); }
}
