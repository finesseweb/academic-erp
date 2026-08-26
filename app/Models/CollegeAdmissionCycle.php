<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollegeAdmissionCycle extends Model
{
    protected $fillable = [
        'college_id', 'college_program_offering_id', 'academic_session_id', 'name', 'code',
        'application_start_date', 'application_end_date',
        'admission_start_date', 'admission_end_date',
        'status', 'remarks', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'application_start_date' => 'date',
        'application_end_date' => 'date',
        'admission_start_date' => 'date',
        'admission_end_date' => 'date',
    ];

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function programOffering(): BelongsTo
    {
        return $this->belongsTo(CollegeProgramOffering::class, 'college_program_offering_id');
    }

    /**
     * Compatibility/derived relation. Program Offering is the authoritative parent;
     * academic_session_id is retained as a derived snapshot for existing records/queries.
     */
    public function academicSession(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(CollegeAdmissionApplication::class, 'college_admission_cycle_id');
    }
}
