<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AcademicPolicyGradeBand extends Model
{
    protected $fillable=['academic_policy_id','minimum_percent','maximum_percent','grade_code','grade_label','grade_point','is_passing','display_order'];
    protected function casts(): array { return ['minimum_percent'=>'decimal:2','maximum_percent'=>'decimal:2','grade_point'=>'decimal:2','is_passing'=>'boolean','display_order'=>'integer']; }
    public function academicPolicy(): BelongsTo { return $this->belongsTo(AcademicPolicy::class); }
}
