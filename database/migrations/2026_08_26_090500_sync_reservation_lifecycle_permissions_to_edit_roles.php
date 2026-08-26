<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $updatePermissionId = DB::table('permissions')
            ->where('code', 'college_reservation.update')
            ->value('id');

        $lifecyclePermissionIds = DB::table('permissions')
            ->whereIn('code', ['college_reservation.enable', 'college_reservation.disable'])
            ->pluck('id');

        if (! $updatePermissionId || $lifecyclePermissionIds->isEmpty()) {
            return;
        }

        // Any role already trusted to edit a College Reservation Plan should also
        // receive its explicit lifecycle controls. This keeps custom/delegated
        // College roles consistent with the Reservation page contract.
        $roleIds = DB::table('role_permissions')
            ->where('permission_id', $updatePermissionId)
            ->pluck('role_id')
            ->unique();

        foreach ($roleIds as $roleId) {
            foreach ($lifecyclePermissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        // Permission synchronization is intentionally non-destructive.
    }
};
