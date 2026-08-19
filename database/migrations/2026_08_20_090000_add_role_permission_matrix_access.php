<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('role_permissions', fn (Blueprint $table) => $table->index('permission_id', 'role_permissions_permission_idx'));
        $now = now();
        $roleId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');
        foreach ([
            ['permission.assign_to_role', 'assign_to_role', 'Assign implemented permissions to custom roles', true],
            ['permission.remove_from_role', 'remove_from_role', 'Remove permissions from custom roles', true],
        ] as [$code, $action, $description, $sensitive]) {
            $permissionId = DB::table('permissions')->insertGetId([
                'code' => $code, 'module' => 'Access & Security', 'resource' => 'permission', 'action' => $action,
                'description' => $description, 'is_sensitive' => $sensitive, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now,
            ]);
            if ($roleId) {
                DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('code', ['permission.assign_to_role', 'permission.remove_from_role'])->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        Schema::table('role_permissions', fn (Blueprint $table) => $table->dropIndex('role_permissions_permission_idx'));
    }
};
