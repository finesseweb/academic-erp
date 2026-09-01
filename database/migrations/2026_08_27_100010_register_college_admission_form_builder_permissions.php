<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        ['college_admission_form.view', 'view', 'View Admission Form Setup', false],
        ['college_admission_form.manage', 'manage', 'Manage Admission Form templates, steps and fields', true],
        ['college_admission_form.map', 'map', 'Map Admission Forms to admission scopes', true],
        ['college_application_fee.manage', 'manage', 'Manage scoped Application Fee rules', true],
    ];

    public function up(): void
    {
        $now = now();
        foreach ($this->permissions as [$code, $action, $description, $sensitive]) {
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'resource' => str_starts_with($code, 'college_application_fee') ? 'college_application_fee' : 'college_admission_form',
                'action' => $action,
                'module' => 'Admission',
                'description' => $description,
                'is_sensitive' => $sensitive,
                'is_college_delegable' => true,
                'status' => 'ACTIVE',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')->whereIn('code', array_column($this->permissions, 0))->pluck('id');
        foreach (['SUPER_ADMIN', 'COLLEGE_ADMIN'] as $roleCode) {
            $roleId = DB::table('roles')->where('code', $roleCode)->value('id');
            if (! $roleId) continue;
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $codes = array_column($this->permissions, 0);
        $ids = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
