<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        DB::table('permissions')->updateOrInsert(['code' => 'college_fee_structure.adopt'], [
            'module' => 'Fee Management', 'resource' => 'college_fee_structure', 'action' => 'adopt',
            'description' => 'Adopt or stop using optional University Fee Structures',
            'is_sensitive' => false, 'is_college_delegable' => true, 'status' => 'ACTIVE',
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $permissionId = DB::table('permissions')->where('code', 'college_fee_structure.adopt')->value('id');
        foreach (['SUPER_ADMIN', 'COLLEGE_ADMIN'] as $roleCode) {
            $roleId = DB::table('roles')->where('code', $roleCode)->where('status', 'ACTIVE')->value('id');
            if ($roleId && $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('code', 'college_fee_structure.adopt')->value('id');
        if ($permissionId) DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('code', 'college_fee_structure.adopt')->delete();
    }
};
