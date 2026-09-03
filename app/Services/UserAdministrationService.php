<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserAdministrationService
{
    public function create(array $data, int $actorId, ?string $ip, string $creatorScopeType = 'UNIVERSITY'): User
    {
        return DB::transaction(function () use ($data, $actorId, $ip, $creatorScopeType) {
            $data['email'] = mb_strtolower(trim($data['email']));
            $data['created_by_user_id'] = $actorId;
            $data['created_by_scope_type'] = $creatorScopeType;
            if (User::query()->whereRaw('LOWER(email) = ?', [$data['email']])->exists()) {
                throw ValidationException::withMessages(['email' => 'A user account with this email already exists. Duplicate users cannot be created.']);
            }
            $user = User::query()->create($data);
            $this->audit('USER_CREATED', $user, $actorId, $ip, null, $user->only(['name', 'email', 'mobile', 'account_type', 'status']));

            return $user;
        });
    }

    public function update(User $user, array $data, int $actorId, ?string $ip): User
    {
        return DB::transaction(function () use ($user, $data, $actorId, $ip) {
            $data['email'] = mb_strtolower(trim($data['email']));
            if (User::query()->whereKeyNot($user->id)->whereRaw('LOWER(email) = ?', [$data['email']])->exists()) {
                throw ValidationException::withMessages(['email' => 'Another user account already uses this email.']);
            }
            $before = $user->only(array_keys($data));
            $user->update($data);
            $this->audit('USER_UPDATED', $user, $actorId, $ip, $before, $user->fresh()->only(array_keys($data)));

            return $user->fresh();
        });
    }

    public function status(User $user, string $status, int $actorId, ?string $ip): User
    {
        return DB::transaction(function () use ($user, $status, $actorId, $ip) {
            $before = ['status' => $user->status];
            $user->update(['status' => $status]);
            if ($status === 'INACTIVE') {
                DB::table('sessions')->where('user_id', $user->id)->delete();
            } $this->audit($status === 'ACTIVE' ? 'USER_ENABLED' : 'USER_DISABLED', $user, $actorId, $ip, $before, ['status' => $status]);

            return $user->fresh();
        });
    }

    public function auditPasswordReset(User $user, int $actorId, ?string $ip): void
    {
        $this->audit('USER_PASSWORD_RESET_INITIATED', $user, $actorId, $ip, null, ['email' => $user->email]);
    }

    private function audit(string $event, User $user, int $actorId, ?string $ip, ?array $before, array $after): void
    {
        DB::table('audit_logs')->insert(['actor_user_id' => $actorId, 'event' => $event, 'resource_type' => 'User', 'resource_id' => $user->id, 'scope_type' => $user->primary_college_id ? 'COLLEGE' : null, 'scope_reference' => $user->primary_college_id ? "college:{$user->primary_college_id}" : null, 'before' => $before ? json_encode($before) : null, 'after' => json_encode($after), 'ip_address' => $ip, 'created_at' => now()]);
    }
}
