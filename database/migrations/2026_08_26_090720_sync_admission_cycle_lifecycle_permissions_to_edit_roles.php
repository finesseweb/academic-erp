<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $updatePermissionId = DB::table('permissions')
            ->where('code', 'college_admission_cycle.update')
            ->value('id');

        $lifecyclePermissionIds = DB::table('permissions')
            ->whereIn('code', [
                'college_admission_cycle.enable',
                'college_admission_cycle.disable',
            ])
            ->pluck('id');

        if (! $updatePermissionId || $lifecyclePermissionIds->isEmpty()) {
            return;
        }

        $roleIds = DB::table('role_permissions')
            ->where('permission_id', $updatePermissionId)
            ->pluck('role_id')
            ->unique();

        $now = now();
        foreach ($roleIds as $roleId) {
            foreach ($lifecyclePermissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        // Permission synchronization is intentionally not reversed because
        // removing access could unexpectedly revoke an administrator's
        // explicitly granted lifecycle permissions.
    }
};
