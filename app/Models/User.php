<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
#[Fillable(['name', 'email', 'mobile', 'account_type', 'status', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

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
            /* @chisel-2fa */
            'two_factor_confirmed_at' => 'datetime',
            /* @end-chisel-2fa */
        ];
    }
}
