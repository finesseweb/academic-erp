<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->boolean('is_college_delegable')->default(false)->after('is_sensitive');
            $table->index(['is_college_delegable', 'status', 'module'], 'permissions_college_delegable_idx');
        });
        DB::table('permissions')->where('resource', 'college_user')->update(['is_college_delegable' => true]);

        $now = now();
        $codes = [
            ['college_permission.assign', 'college_permission', 'assign', 'Assign delegated permissions to College-owned roles'],
            ['college_permission.remove', 'college_permission', 'remove', 'Remove delegated permissions from College-owned roles'],
            ['college_role.assign', 'college_role', 'assign', 'Assign College-owned roles to College users'],
            ['college_role.unassign', 'college_role', 'unassign', 'Remove College-owned role assignments'],
            ['college_scope.update', 'college_scope', 'update', 'Update College role assignment lifecycle and effective dates'],
        ];
        $roleIds = DB::table('roles')->whereIn('code', ['SUPER_ADMIN', 'COLLEGE_ADMIN'])->pluck('id');
        foreach ($codes as [$code, $resource, $action, $description]) {
            $permissionId = DB::table('permissions')->insertGetId(['code' => $code, 'module' => 'College Access', 'resource' => $resource, 'action' => $action, 'description' => $description, 'is_sensitive' => true, 'is_college_delegable' => false, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now]);
            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('code', ['college_permission.assign', 'college_permission.remove', 'college_role.assign', 'college_role.unassign', 'college_scope.update'])->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropIndex('permissions_college_delegable_idx');
            $table->dropColumn('is_college_delegable');
        });
    }
};
