<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RolePermissionService
{
    public function sync(Role $role, array $permissionIds, int $actorId, ?string $ip): array
    {
        return DB::transaction(function () use ($role, $permissionIds, $actorId, $ip) {
            $beforeIds = $role->permissions()->pluck('permissions.id')->all();
            $addedIds = array_values(array_diff($permissionIds, $beforeIds));
            $removedIds = array_values(array_diff($beforeIds, $permissionIds));
            $role->permissions()->sync($permissionIds);
            $codes = Permission::query()->whereIn('id', [...$addedIds, ...$removedIds])->pluck('code', 'id');
            $added = array_values(array_map(fn ($id) => $codes[$id], $addedIds));
            $removed = array_values(array_map(fn ($id) => $codes[$id], $removedIds));
            DB::table('audit_logs')->insert([
                'actor_user_id' => $actorId, 'event' => 'ROLE_PERMISSIONS_UPDATED', 'resource_type' => 'Role', 'resource_id' => $role->id,
                'before' => json_encode(['permission_codes' => $removed]), 'after' => json_encode(['added' => $added, 'removed' => $removed]),
                'ip_address' => $ip, 'created_at' => now(),
            ]);

            return compact('added', 'removed');
        });
    }
}
