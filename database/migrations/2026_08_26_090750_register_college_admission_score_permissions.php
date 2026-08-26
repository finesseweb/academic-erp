<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        ['college_admission_score.view', 'view', 'View Admission Score Capture / Normalization', false],
        ['college_admission_score.manage', 'manage', 'Capture and normalize Admission scores', true],
    ];

    public function up(): void
    {
        $now = now();
        foreach ($this->permissions as [$code, $action, $description, $sensitive]) {
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'resource' => 'college_admission_score', 'action' => $action, 'module' => 'Admission',
                'description' => $description, 'is_sensitive' => $sensitive,
                'is_college_delegable' => true, 'status' => 'ACTIVE',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $ids = DB::table('permissions')->whereIn('code', array_column($this->permissions, 0))->pluck('id');
        foreach (['SUPER_ADMIN','COLLEGE_ADMIN'] as $roleCode) {
            $roleId = DB::table('roles')->where('code', $roleCode)->value('id');
            if (! $roleId) continue;
            foreach ($ids as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id'=>$roleId,'permission_id'=>$permissionId],
                    ['created_at'=>$now,'updated_at'=>$now]
                );
            }
        }
    }

    public function down(): void
    {
        $codes = array_column($this->permissions, 0);
        $ids = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('code', $codes)->delete();
    }
};
