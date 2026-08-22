<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            ['code' => 'curriculum.view', 'resource' => 'curriculum', 'action' => 'view', 'description' => 'View curriculum headers', 'is_sensitive' => false],
            ['code' => 'curriculum.create', 'resource' => 'curriculum', 'action' => 'create', 'description' => 'Create curriculum headers', 'is_sensitive' => false],
            ['code' => 'curriculum.update', 'resource' => 'curriculum', 'action' => 'update', 'description' => 'Update curriculum headers', 'is_sensitive' => false],
            ['code' => 'curriculum.disable', 'resource' => 'curriculum', 'action' => 'disable', 'description' => 'Retire curriculum headers', 'is_sensitive' => true],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $permission['code']],
                [
                    'resource' => $permission['resource'],
                    'action' => $permission['action'],
                    'module' => 'ACADEMIC_SETUP',
                    'description' => $permission['description'],
                    'is_sensitive' => $permission['is_sensitive'],
                    'is_college_delegable' => false,
                    'status' => 'ACTIVE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $superAdminRoleId = DB::table('roles')
            ->where('code', 'SUPER_ADMIN')
            ->value('id');

        if ($superAdminRoleId) {
            $permissionIds = DB::table('permissions')
                ->whereIn('code', array_column($permissions, 'code'))
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $superAdminRoleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->whereIn('code', [
                'curriculum.view',
                'curriculum.create',
                'curriculum.update',
                'curriculum.disable',
            ])
            ->pluck('id');

        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
