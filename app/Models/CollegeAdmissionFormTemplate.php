<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CollegeAdmissionFormTemplate extends Model
{
    protected $casts = ['allow_college_override' => 'boolean'];
    protected $fillable = ['university_id','college_id','parent_template_id','manager_user_id','name','code','owner_scope_type','governance_mode','allow_college_override','admission_mode','status','description','created_by','updated_by'];

    public function university(): BelongsTo { return $this->belongsTo(University::class); }
    public function college(): BelongsTo { return $this->belongsTo(College::class); }
    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_template_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_template_id'); }
    public function manager(): BelongsTo { return $this->belongsTo(User::class, 'manager_user_id'); }
    public function steps(): HasMany { return $this->hasMany(CollegeAdmissionFormStep::class)->orderBy('display_order')->orderBy('id'); }
    public function mappings(): HasMany { return $this->hasMany(CollegeAdmissionFormMapping::class); }
}
