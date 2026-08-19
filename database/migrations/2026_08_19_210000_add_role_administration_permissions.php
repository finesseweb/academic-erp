<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', fn (Blueprint $table) => $table->index(['owner_scope_type', 'owner_scope_reference', 'status'], 'roles_owner_scope_status_idx'));
        $now = now();
        $roleId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');
        foreach ([['role.view', 'view', 'View roles'], ['role.create', 'create', 'Create custom roles'], ['role.update', 'update', 'Update custom roles'], ['role.disable', 'disable', 'Activate or deactivate custom roles']] as [$code,$action,$description]) {
            $id = DB::table('permissions')->insertGetId(['code' => $code, 'module' => 'Access & Security', 'resource' => 'role', 'action' => $action, 'description' => $description, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now]);
            if ($roleId) {
                DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('code', ['role.view', 'role.create', 'role.update', 'role.disable'])->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        Schema::table('roles', fn (Blueprint $table) => $table->dropIndex('roles_owner_scope_status_idx'));
    }
};
