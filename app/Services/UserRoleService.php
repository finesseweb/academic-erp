<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserRoleService
{
    public function assign(User $user, Role $role, array $scope, int $actorId, ?string $ip): UserRole
    {
        return DB::transaction(function () use ($user, $role, $scope, $actorId, $ip) {
            $assignment = UserRole::create(['user_id' => $user->id, 'role_id' => $role->id, ...$scope, 'status' => 'ACTIVE']);
            $this->audit('USER_ROLE_ASSIGNED', $assignment, $actorId, $ip, ['role_code' => $role->code, ...$scope]);

            return $assignment;
        });
    }

    public function remove(UserRole $assignment, int $actorId, ?string $ip): void
    {
        DB::transaction(function () use ($assignment, $actorId, $ip) {
            $assignment->load('role');
            $data = ['role_code' => $assignment->role->code, 'scope_type' => $assignment->scope_type, 'scope_reference' => $assignment->scope_reference];
            $this->audit('USER_ROLE_UNASSIGNED', $assignment, $actorId, $ip, $data);
            $assignment->delete();
        });
    }

    public function updateScope(UserRole $assignment, array $data, int $actorId, ?string $ip): UserRole
    {
        return DB::transaction(function () use ($assignment, $data, $actorId, $ip) {
            $assignment->load('role');
            $before = $this->snapshot($assignment);
            $assignment->update([
                'scope_type' => $data['scope_type'],
                'scope_reference' => $data['scope_reference'],
                'status' => $data['status'],
                'effective_from' => $data['effective_from'] ? Carbon::parse($data['effective_from'])->startOfDay() : null,
                'effective_until' => $data['effective_until'] ? Carbon::parse($data['effective_until'])->endOfDay() : null,
            ]);
            $assignment->refresh()->load('role');
            $this->auditChange('USER_ROLE_SCOPE_UPDATED', $assignment, $actorId, $ip, $before, $this->snapshot($assignment));

            return $assignment;
        });
    }

    private function audit(string $event, UserRole $assignment, int $actorId, ?string $ip, array $data): void
    {
        DB::table('audit_logs')->insert(['actor_user_id' => $actorId, 'event' => $event, 'resource_type' => 'UserRole', 'resource_id' => $assignment->id, 'before' => $event === 'USER_ROLE_UNASSIGNED' ? json_encode($data) : null, 'after' => $event === 'USER_ROLE_ASSIGNED' ? json_encode($data) : null, 'ip_address' => $ip, 'created_at' => now()]);
    }

    private function auditChange(string $event, UserRole $assignment, int $actorId, ?string $ip, array $before, array $after): void
    {
        DB::table('audit_logs')->insert(['actor_user_id' => $actorId, 'event' => $event, 'resource_type' => 'UserRole', 'resource_id' => $assignment->id, 'before' => json_encode($before), 'after' => json_encode($after), 'ip_address' => $ip, 'created_at' => now()]);
    }

    private function snapshot(UserRole $assignment): array
    {
        return [
            'role_code' => $assignment->role->code,
            'scope_type' => $assignment->scope_type,
            'scope_reference' => $assignment->scope_reference,
            'status' => $assignment->status,
            'effective_from' => $assignment->effective_from?->toIso8601String(),
            'effective_until' => $assignment->effective_until?->toIso8601String(),
        ];
    }
}
