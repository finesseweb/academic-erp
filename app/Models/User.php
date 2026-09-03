<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'mobile', 'account_type', 'primary_college_id', 'status', 'password', 'created_by_user_id', 'created_by_scope_type'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyEmail, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public function scopeVisibleToUniversityAdministration(Builder $query): Builder
    {
        return $query->where('created_by_scope_type', 'UNIVERSITY');
    }

    public function isUniversityManaged(): bool
    {
        return $this->created_by_scope_type === 'UNIVERSITY';
    }

    public function primaryCollege(): BelongsTo
    {
        return $this->belongsTo(College::class, 'primary_college_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withPivot(['id', 'scope_type', 'scope_reference', 'status', 'effective_from', 'effective_until'])->withTimestamps();
    }

    public function hasPermission(string $code, string $scopeType = 'UNIVERSITY', string $scopeReference = 'university'): bool
    {
        return $this->roles()
            ->where('roles.status', 'ACTIVE')
            ->wherePivot('status', 'ACTIVE')
            ->wherePivot('scope_type', $scopeType)
            ->wherePivot('scope_reference', $scopeReference)
            ->where(fn ($query) => $query->whereNull('user_roles.effective_from')->orWhere('user_roles.effective_from', '<=', now()))
            ->where(fn ($query) => $query->whereNull('user_roles.effective_until')->orWhere('user_roles.effective_until', '>=', now()))
            ->whereHas('permissions', fn ($query) => $query->where('code', $code))
            ->exists();
    }

    /** @return array<int, string> */
    public function permissionCodes(string $scopeType = 'UNIVERSITY', string $scopeReference = 'university'): array
    {
        return $this->roles()
            ->where('roles.status', 'ACTIVE')
            ->wherePivot('status', 'ACTIVE')
            ->wherePivot('scope_type', $scopeType)
            ->wherePivot('scope_reference', $scopeReference)
            ->where(fn ($query) => $query->whereNull('user_roles.effective_from')->orWhere('user_roles.effective_from', '<=', now()))
            ->where(fn ($query) => $query->whereNull('user_roles.effective_until')->orWhere('user_roles.effective_until', '>=', now()))
            ->with('permissions:id,code')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('code'))
            ->unique()->sort()->values()->all();
    }

    public function hasCollegePermission(string $code, int $collegeId): bool
    {
        return $this->hasPermission($code, 'COLLEGE', "college:{$collegeId}");
    }

    /** @return array<int, int> */
    public function activeCollegeScopeIds(): array
    {
        return $this->roles()->where('roles.status', 'ACTIVE')->wherePivot('status', 'ACTIVE')->wherePivot('scope_type', 'COLLEGE')
            ->where(fn ($q) => $q->whereNull('user_roles.effective_from')->orWhere('user_roles.effective_from', '<=', now()))
            ->where(fn ($q) => $q->whereNull('user_roles.effective_until')->orWhere('user_roles.effective_until', '>=', now()))
            ->pluck('user_roles.scope_reference')->map(fn ($scope) => (int) str_replace('college:', '', $scope))->filter()->unique()->values()->all();
    }

    /** @return array<int, string> */
    public function allEffectivePermissionCodes(): array
    {
        $codes = collect($this->permissionCodes());
        foreach ($this->activeCollegeScopeIds() as $collegeId) {
            $codes = $codes->merge($this->permissionCodes('COLLEGE', "college:{$collegeId}"));
        }

        return $codes->unique()->sort()->values()->all();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
