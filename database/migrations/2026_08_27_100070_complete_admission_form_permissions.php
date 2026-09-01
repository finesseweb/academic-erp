<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        ['college_admission_form.view', 'college_admission_form', 'view', 'View Admission Form Setup', false],
        ['college_admission_form.create', 'college_admission_form', 'create', 'Create Admission Form templates', false],
        ['college_admission_form.update', 'college_admission_form', 'update', 'Update Admission Form template governance and settings', true],
        ['college_admission_form.status', 'college_admission_form', 'status', 'Activate or retire Admission Form templates', true],
        ['college_admission_form.step_create', 'college_admission_form', 'step_create', 'Add steps to Admission Form templates', false],
        ['college_admission_form.field_create', 'college_admission_form', 'field_create', 'Add fields and field rules to Admission Form templates', false],
        ['college_admission_form.map', 'college_admission_form', 'map', 'Map Admission Forms to academic/admission scopes', true],
        ['college_application_fee.manage', 'college_application_fee', 'manage', 'Manage scoped Application Fee rules', true],
    ];

    public function up(): void
    {
        $now = now();
        $legacyManageId = DB::table('permissions')->where('code', 'college_admission_form.manage')->value('id');
        $legacyRoleIds = $legacyManageId
            ? DB::table('role_permissions')->where('permission_id', $legacyManageId)->pluck('role_id')->all()
            : [];

        foreach ($this->permissions as [$code, $resource, $action, $description, $sensitive]) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $code],
                [
                    'module' => 'Admission',
                    'resource' => $resource,
                    'action' => $action,
                    'description' => $description,
                    'is_sensitive' => $sensitive,
                    'is_college_delegable' => true,
                    'status' => 'ACTIVE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $granularCodes = [
            'college_admission_form.create',
            'college_admission_form.update',
            'college_admission_form.status',
            'college_admission_form.step_create',
            'college_admission_form.field_create',
        ];
        $granularIds = DB::table('permissions')->whereIn('code', $granularCodes)->pluck('id');

        // Preserve capability for any role that previously had the broad legacy manage permission.
        foreach ($legacyRoleIds as $roleId) {
            foreach ($granularIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        // SUPER_ADMIN remains the default University owner of all implemented capabilities.
        $superAdminId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');
        if ($superAdminId) {
            $ids = DB::table('permissions')->whereIn('code', array_column($this->permissions, 0))->pluck('id');
            foreach ($ids as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $superAdminId, 'permission_id' => $permissionId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        // Retire the broad compatibility permission so the Permission catalog stays action-specific.
        if ($legacyManageId) {
            DB::table('permissions')->where('id', $legacyManageId)->update([
                'status' => 'INACTIVE',
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('code', 'college_admission_form.manage')->update([
            'status' => 'ACTIVE',
            'updated_at' => now(),
        ]);
    }
};
