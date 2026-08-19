<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', fn (Blueprint $table) => $table->index(['module', 'status'], 'permissions_module_status_idx'));
        $now = now();
        $permissionId = DB::table('permissions')->insertGetId([
            'code' => 'permission.view', 'module' => 'Access & Security', 'resource' => 'permission', 'action' => 'view',
            'description' => 'View the implemented permission catalog', 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $roleId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');
        if ($roleId) {
            DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        $id = DB::table('permissions')->where('code', 'permission.view')->value('id');
        if ($id) {
            DB::table('role_permissions')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('id', $id)->delete();
        }
        Schema::table('permissions', fn (Blueprint $table) => $table->dropIndex('permissions_module_status_idx'));
    }
};
