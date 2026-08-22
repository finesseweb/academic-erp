<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissions = [
            [
                'code' => 'curriculum.view',
                'action' => 'view',
                'description' => 'View Curriculum Header',
                'is_sensitive' => false,
            ],
            [
                'code' => 'curriculum.create',
                'action' => 'create',
                'description' => 'Create Curriculum Header',
                'is_sensitive' => false,
            ],
            [
                'code' => 'curriculum.update',
                'action' => 'update',
                'description' => 'Update Curriculum Header',
                'is_sensitive' => false,
            ],
            [
                'code' => 'curriculum.disable',
                'action' => 'disable',
                'description' => 'Retire Curriculum Header',
                'is_sensitive' => true,
            ],
        ];

        foreach ($permissions as $permission) {
            $existing = DB::table('permissions')
                ->where('code', $permission['code'])
                ->first();

            $values = [
                'module' => 'ACADEMIC_SETUP',
                'resource' => 'curriculum',
                'action' => $permission['action'],
                'description' => $permission['description'],
                'is_sensitive' => $permission['is_sensitive'],
                'is_college_delegable' => false,
                'status' => 'ACTIVE',
                'updated_at' => $now,
            ];

            if ($existing) {
                DB::table('permissions')
                    ->where('id', $existing->id)
                    ->update($values);
            } else {
                DB::table('permissions')->insert([
                    'code' => $permission['code'],
                    ...$values,
                    'created_at' => $now,
                ]);
            }
        }

        $superAdminRoleId = DB::table('roles')
            ->where('code', 'SUPER_ADMIN')
            ->where('status', 'ACTIVE')
            ->value('id');

        if (! $superAdminRoleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('code', array_column($permissions, 'code'))
            ->where('status', 'ACTIVE')
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            $exists = DB::table('role_permissions')
                ->where('role_id', $superAdminRoleId)
                ->where('permission_id', $permissionId)
                ->exists();

            if (! $exists) {
                DB::table('role_permissions')->insert([
                    'role_id' => $superAdminRoleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive.
        // This is a permission repair migration and must not remove
        // authorization history or grants during rollback.
    }
};
