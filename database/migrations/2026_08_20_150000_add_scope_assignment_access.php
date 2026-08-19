<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_roles', function (Blueprint $table) {
            $table->index(
                ['user_id', 'status', 'effective_from', 'effective_until'],
                'user_roles_effective_access_idx'
            );
        });

        $now = now();
        $permissionId = DB::table('permissions')->insertGetId([
            'code' => 'scope.update',
            'module' => 'Access & Security',
            'resource' => 'scope',
            'action' => 'update',
            'description' => 'Update role-assignment scope and effective access period',
            'is_sensitive' => true,
            'status' => 'ACTIVE',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $superAdminRoleId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');

        if ($superAdminRoleId) {
            DB::table('role_permissions')->insert([
                'role_id' => $superAdminRoleId,
                'permission_id' => $permissionId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->where('code', 'scope.update')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();

        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropIndex('user_roles_effective_access_idx');
        });
    }
};
