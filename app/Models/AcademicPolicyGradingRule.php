<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class AcademicPolicyGradingRule extends Model
{
    protected $fillable=['academic_policy_id','grading_basis','maximum_grade_point','calculate_sgpa','calculate_cgpa','sgpa_decimal_places','cgpa_decimal_places','rounding_rule','notes','created_by','updated_by'];
    protected function casts(): array { return ['maximum_grade_point'=>'decimal:2','calculate_sgpa'=>'boolean','calculate_cgpa'=>'boolean','sgpa_decimal_places'=>'integer','cgpa_decimal_places'=>'integer']; }
    public function academicPolicy(): BelongsTo { return $this->belongsTo(AcademicPolicy::class); }
}
