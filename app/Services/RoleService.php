<?php

namespace App\Services;

use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleService
{
    public function create(array $data, int $actorId, ?string $ip): Role
    {
        return DB::transaction(function () use ($data, $actorId, $ip) {
            $role = Role::create([...$data, 'is_system_role' => false, 'owner_scope_type' => 'UNIVERSITY', 'owner_scope_reference' => 'university']);
            $this->audit('ROLE_CREATED', $role, $actorId, $ip, null, $role->toArray());

            return $role;
        });
    }

    public function createForCollege(array $data, int $collegeId, int $actorId, ?string $ip): Role
    {
        return DB::transaction(function () use ($data, $collegeId, $actorId, $ip) {
            $role = Role::create([...$data, 'is_system_role' => false, 'owner_scope_type' => 'COLLEGE', 'owner_scope_reference' => "college:{$collegeId}", 'status' => 'ACTIVE']);
            $this->audit('ROLE_CREATED', $role, $actorId, $ip, null, $role->toArray());

            return $role;
        });
    }

    public function update(Role $role, array $data, int $actorId, ?string $ip): Role
    {
        return DB::transaction(function () use ($role, $data, $actorId, $ip) {
            $before = $role->only(array_keys($data));
            $role->update($data);
            $this->audit('ROLE_UPDATED', $role, $actorId, $ip, $before, $role->fresh()->only(array_keys($data)));

            return $role->fresh();
        });
    }

    public function status(Role $role, string $status, int $actorId, ?string $ip): Role
    {
        return DB::transaction(function () use ($role, $status, $actorId, $ip) {
            $before = ['status' => $role->status];
            $role->update(['status' => $status]);
            $this->audit('ROLE_STATUS_CHANGED', $role, $actorId, $ip, $before, ['status' => $status]);

            return $role->fresh();
        });
    }

    private function audit(string $event, Role $role, int $actorId, ?string $ip, ?array $before, array $after): void
    {
        DB::table('audit_logs')->insert(['actor_user_id' => $actorId, 'event' => $event, 'resource_type' => 'Role', 'resource_id' => $role->id, 'scope_type' => $role->owner_scope_type === 'COLLEGE' ? 'COLLEGE' : null, 'scope_reference' => $role->owner_scope_type === 'COLLEGE' ? $role->owner_scope_reference : null, 'before' => $before ? json_encode($before) : null, 'after' => json_encode($after), 'ip_address' => $ip, 'created_at' => now()]);
    }
}
