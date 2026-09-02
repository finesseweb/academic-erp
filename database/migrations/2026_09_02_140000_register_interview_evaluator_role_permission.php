<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private string $code = 'college_admission_interview.evaluate';

    public function up(): void
    {
        $now = now();
        DB::table('permissions')->updateOrInsert(
            ['code' => $this->code],
            [
                'resource' => 'college_admission_interview',
                'action' => 'evaluate',
                'module' => 'Admission',
                'description' => 'Eligible to serve as an Admission Interview evaluator',
                'is_sensitive' => true,
                'is_college_delegable' => true,
                'status' => 'ACTIVE',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $permissionId = DB::table('permissions')->where('code', $this->code)->value('id');
        foreach (['SUPER_ADMIN', 'COLLEGE_ADMIN'] as $roleCode) {
            $roleId = DB::table('roles')->where('code', $roleCode)->value('id');
            if (! $roleId || ! $permissionId) continue;
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $roleId, 'permission_id' => $permissionId],
                ['created_at' => $now, 'updated_at' => $now]
            );
        }
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('code', $this->code)->value('id');
        if ($permissionId) DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
        DB::table('permissions')->where('code', $this->code)->delete();
    }
};
