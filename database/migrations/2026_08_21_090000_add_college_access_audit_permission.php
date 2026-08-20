<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissionId = DB::table('permissions')->insertGetId(['code' => 'college_audit.view', 'module' => 'College Access', 'resource' => 'college_audit', 'action' => 'view', 'description' => 'View immutable access history for an assigned College', 'is_sensitive' => true, 'is_college_delegable' => false, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now]);
        foreach (DB::table('roles')->whereIn('code', ['SUPER_ADMIN', 'COLLEGE_ADMIN'])->pluck('id') as $roleId) {
            DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('code', 'college_audit.view')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
