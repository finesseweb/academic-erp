<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeAdmissionSelectionRuleTieBreaker extends Model
{
    /**
     * The documented/migrated table intentionally uses "tiebreakers" as one word.
     * Pin it explicitly because Laravel would otherwise infer "tie_breakers" from
     * the model name and query a table that does not exist.
     */
    protected $table = 'college_admission_selection_rule_tiebreakers';

    protected $fillable = [
        'college_admission_selection_rule_id',
        'priority',
        'criterion',
        'comparison_direction',
        'criterion_reference',
    ];

    protected $casts = [
        'priority' => 'integer',
    ];

    public function selectionRule(): BelongsTo
    {
        return $this->belongsTo(CollegeAdmissionSelectionRule::class, 'college_admission_selection_rule_id');
    }
}
