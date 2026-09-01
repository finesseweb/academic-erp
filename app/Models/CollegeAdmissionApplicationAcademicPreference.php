<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeAdmissionApplicationAcademicPreference extends Model
{
    protected $fillable = [
        'college_admission_application_id', 'college_program_offering_id', 'curriculum_id',
        'discipline_id', 'specialization_id', 'curriculum_snapshot',
    ];

    protected $casts = ['curriculum_snapshot' => 'array'];

    public function application(): BelongsTo { return $this->belongsTo(CollegeAdmissionApplication::class, 'college_admission_application_id'); }
    public function offering(): BelongsTo { return $this->belongsTo(CollegeProgramOffering::class, 'college_program_offering_id'); }
    public function curriculum(): BelongsTo { return $this->belongsTo(Curriculum::class); }
    public function discipline(): BelongsTo { return $this->belongsTo(AcademicDiscipline::class, 'discipline_id'); }
    public function specialization(): BelongsTo { return $this->belongsTo(AcademicDiscipline::class, 'specialization_id'); }
}
