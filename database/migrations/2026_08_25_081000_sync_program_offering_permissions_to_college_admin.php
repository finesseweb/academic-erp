<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissionCodes = [
        'college_program_offering.view',
        'college_program_offering.create',
        'college_program_offering.update',
        'college_program_offering.enable',
        'college_program_offering.disable',
    ];

    public function up(): void
    {
        $now = now();

        $permissionIds = DB::table('permissions')
            ->whereIn('code', $this->permissionCodes)
            ->where('status', 'ACTIVE')
            ->pluck('id');

        if ($permissionIds->count() !== count($this->permissionCodes)) {
            throw new RuntimeException(
                'College Program Offering permissions must be registered before protected-role synchronization.'
            );
        }

        // Keep the permission records explicitly College-delegable for custom College roles.
        DB::table('permissions')
            ->whereIn('id', $permissionIds)
            ->update([
                'is_college_delegable' => true,
                'updated_at' => $now,
            ]);

        // Protected system roles are synchronized by migrations, not edited manually.
        $roleIds = DB::table('roles')
            ->whereIn('code', ['SUPER_ADMIN', 'COLLEGE_ADMIN'])
            ->where('status', 'ACTIVE')
            ->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
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
        // Restore the state that existed before this corrective migration:
        // SUPER_ADMIN grants came from the original Program Offering migration,
        // so only the newly-added COLLEGE_ADMIN mappings are removed here.
        $collegeAdminRoleId = DB::table('roles')
            ->where('code', 'COLLEGE_ADMIN')
            ->value('id');

        if (! $collegeAdminRoleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('code', $this->permissionCodes)
            ->pluck('id');

        DB::table('role_permissions')
            ->where('role_id', $collegeAdminRoleId)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }
};
