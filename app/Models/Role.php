<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $guarded = [];

    public function scopeVisibleToUniversityAdministration(Builder $query): Builder
    {
        return $query->where('created_by_scope_type', 'UNIVERSITY');
    }

    public function isUniversityManaged(): bool
    {
        return $this->created_by_scope_type === 'UNIVERSITY';
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')->withPivot(['scope_type', 'scope_reference', 'status'])->withTimestamps();
    }
}
