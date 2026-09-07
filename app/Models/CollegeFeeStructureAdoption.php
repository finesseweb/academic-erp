<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollegeFeeStructureAdoption extends Model
{
    protected $fillable = ['university_fee_structure_id','college_id','status','created_by','updated_by'];

    public function structure(): BelongsTo { return $this->belongsTo(FeeStructure::class, 'university_fee_structure_id'); }
    public function college(): BelongsTo { return $this->belongsTo(College::class); }
}
