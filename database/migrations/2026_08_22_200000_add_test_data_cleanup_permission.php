<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $code = 'test_data_cleanup.manage';

        if (! DB::table('permissions')->where('code', $code)->exists()) {
            $row = [
                'code' => $code,
                'module' => 'System Maintenance',
                'resource' => 'test_data_cleanup',
                'action' => 'manage',
                'description' =>
                    'Open and execute controlled test data cleanup tools',
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasColumn('permissions', 'is_sensitive')) {
                $row['is_sensitive'] = true;
            }

            if (Schema::hasColumn(
                'permissions',
                'is_college_delegable'
            )) {
                $row['is_college_delegable'] = false;
            }

            DB::table('permissions')->insert($row);
        }

        $superAdminId = DB::table('roles')
            ->where('code', 'SUPER_ADMIN')
            ->value('id');

        $permissionId = DB::table('permissions')
            ->where('code', $code)
            ->value('id');

        if (
            $superAdminId &&
            $permissionId &&
            ! DB::table('role_permissions')
                ->where('role_id', $superAdminId)
                ->where('permission_id', $permissionId)
                ->exists()
        ) {
            DB::table('role_permissions')->insert([
                'role_id' => $superAdminId,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')
            ->where('code', 'test_data_cleanup.manage')
            ->value('id');

        if ($permissionId) {
            DB::table('role_permissions')
                ->where('permission_id', $permissionId)
                ->delete();

            DB::table('permissions')
                ->where('id', $permissionId)
                ->delete();
        }
    }
};
