<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CollegeAdmissionFormAccessControl extends Model
{
    protected $fillable = [
        'university_id', 'college_id', 'is_enabled', 'governance_mode',
        'allow_college_fee_override', 'enabled_by', 'enabled_at', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'allow_college_fee_override' => 'boolean',
            'enabled_at' => 'datetime',
        ];
    }

    public function university(): BelongsTo { return $this->belongsTo(University::class); }
    public function college(): BelongsTo { return $this->belongsTo(College::class); }
    public function allowedRoles(): BelongsToMany { return $this->belongsToMany(Role::class, 'college_admission_form_access_roles'); }

    public static function forCollege(College $college): self
    {
        return static::query()->where('college_id', $college->id)->first() ?? new static([
            'university_id' => $college->university_id,
            'college_id' => $college->id,
            'is_enabled' => false,
            'governance_mode' => 'UNIVERSITY_BASE_COLLEGE_EXTENSION',
            'allow_college_fee_override' => false,
        ]);
    }
}
