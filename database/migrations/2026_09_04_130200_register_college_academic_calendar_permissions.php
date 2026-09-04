<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        ['college_academic_calendar.view', 'view', 'View College Academic Calendar', false],
        ['college_academic_calendar.create', 'create', 'Adopt University Academic Calendar for College', false],
        ['college_academic_calendar.update', 'update', 'Update College Academic Calendar notes', false],
        ['college_academic_calendar.enable', 'enable', 'Activate College Academic Calendar', true],
        ['college_academic_calendar.disable', 'disable', 'Deactivate College Academic Calendar', true],
        ['college_academic_calendar.override_create', 'override_create', 'Create allowed College Calendar event overrides', false],
        ['college_academic_calendar.override_update', 'override_update', 'Update allowed College Calendar event overrides', false],
        ['college_academic_calendar.override_disable', 'override_disable', 'Enable or disable College Calendar event overrides', true],
    ];

    public function up(): void
    {
        $now = now();
        foreach ($this->permissions as [$code, $action, $description, $sensitive]) {
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'resource' => 'college_academic_calendar',
                'action' => $action,
                'module' => 'College Academic Setup',
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
            $roleId = DB::table('roles')->where('code', $roleCode)->where('status', 'ACTIVE')->value('id');
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
        $permissionIds = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('code', $codes)->delete();
    }
};
