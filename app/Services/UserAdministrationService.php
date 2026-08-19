<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserAdministrationService
{
    public function create(array $data, int $actorId, ?string $ip): User
    {
        return DB::transaction(function () use ($data, $actorId, $ip) {
            $data['email'] = mb_strtolower($data['email']);
            $user = User::query()->create($data);
            $this->audit('USER_CREATED', $user, $actorId, $ip, null, $user->only(['name', 'email', 'mobile', 'account_type', 'status']));

            return $user;
        });
    }

    public function update(User $user, array $data, int $actorId, ?string $ip): User
    {
        return DB::transaction(function () use ($user, $data, $actorId, $ip) {
            $data['email'] = mb_strtolower($data['email']);
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
        DB::table('audit_logs')->insert(['actor_user_id' => $actorId, 'event' => $event, 'resource_type' => 'User', 'resource_id' => $user->id, 'before' => $before ? json_encode($before) : null, 'after' => json_encode($after), 'ip_address' => $ip, 'created_at' => now()]);
    }
}
