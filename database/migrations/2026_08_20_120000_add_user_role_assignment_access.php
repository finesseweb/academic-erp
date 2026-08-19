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
            $table->index(['role_id', 'status'], 'user_roles_role_status_idx');
            $table->index(['scope_type', 'scope_reference', 'status'], 'user_roles_scope_status_idx');
        });
        $now = now();
        $super = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');
        foreach ([['role.assign', 'assign', 'Assign custom roles to users', true], ['role.unassign', 'unassign', 'Remove role assignments from users', true]] as [$code,$action,$description,$sensitive]) {
            $id = DB::table('permissions')->insertGetId(['code' => $code, 'module' => 'Access & Security', 'resource' => 'role', 'action' => $action, 'description' => $description, 'is_sensitive' => $sensitive, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now]);
            if ($super) {
                DB::table('role_permissions')->insert(['role_id' => $super, 'permission_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('code', ['role.assign', 'role.unassign'])->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        Schema::table('user_roles', function (Blueprint $table) {
            $table->dropIndex('user_roles_role_status_idx');
            $table->dropIndex('user_roles_scope_status_idx');
        });
    }
};
