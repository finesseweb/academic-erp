<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('primary_college_id')->nullable()->after('account_type')->constrained('colleges')->restrictOnDelete();
            $table->index(['primary_college_id', 'status', 'name'], 'users_college_status_name_idx');
        });
        $now = now();
        $roleId = DB::table('roles')->insertGetId(['name' => 'College Administrator', 'code' => 'COLLEGE_ADMIN', 'description' => 'Administers one explicitly assigned affiliated College', 'is_system_role' => true, 'owner_scope_type' => 'UNIVERSITY', 'owner_scope_reference' => 'university', 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now]);
        $permissions = [
            ['college_admin.assign', 'college_admin', 'assign', 'Assign a College Administrator'],
            ['college_user.view', 'college_user', 'view', 'View users in an assigned College'], ['college_user.create', 'college_user', 'create', 'Create users in an assigned College'], ['college_user.update', 'college_user', 'update', 'Update users in an assigned College'], ['college_user.disable', 'college_user', 'disable', 'Disable users in an assigned College'], ['college_user.enable', 'college_user', 'enable', 'Enable users in an assigned College'], ['college_user.reset_password', 'college_user', 'reset_password', 'Reset passwords in an assigned College'],
            ['college_role.view', 'college_role', 'view', 'View roles owned by an assigned College'], ['college_role.create', 'college_role', 'create', 'Create roles for an assigned College'], ['college_role.update', 'college_role', 'update', 'Update roles owned by an assigned College'], ['college_role.disable', 'college_role', 'disable', 'Disable roles owned by an assigned College'],
        ];
        $superId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');
        foreach ($permissions as [$code, $resource, $action, $description]) {
            $id = DB::table('permissions')->insertGetId(['code' => $code, 'module' => 'College Access', 'resource' => $resource, 'action' => $action, 'description' => $description, 'is_sensitive' => in_array($action, ['assign', 'disable', 'reset_password'], true), 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now]);
            DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
            if ($superId) {
                DB::table('role_permissions')->insert(['role_id' => $superId, 'permission_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('module', 'College Access')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        $role = DB::table('roles')->where('code', 'COLLEGE_ADMIN')->value('id');
        if ($role) {
            DB::table('user_roles')->where('role_id', $role)->delete();
            DB::table('roles')->where('id', $role)->delete();
        }
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['primary_college_id']);
            $table->dropIndex('users_college_status_name_idx');
            $table->dropColumn('primary_college_id');
        });
    }
};
