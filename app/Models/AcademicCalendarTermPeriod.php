<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicCalendarTermPeriod extends Model
{
    protected $fillable = ['academic_calendar_id','curriculum_term_id','start_date','end_date','allow_college_override','status','created_by','updated_by'];
    protected function casts(): array { return ['start_date'=>'date','end_date'=>'date','allow_college_override'=>'boolean']; }
    public function calendar(): BelongsTo { return $this->belongsTo(AcademicCalendar::class, 'academic_calendar_id'); }
    public function curriculumTerm(): BelongsTo { return $this->belongsTo(CurriculumTerm::class); }
    public function events(): HasMany { return $this->hasMany(AcademicCalendarEvent::class, 'academic_calendar_term_period_id'); }
}
