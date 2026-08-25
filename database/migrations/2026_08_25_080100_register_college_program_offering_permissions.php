<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        ['college_program_offering.view', 'college_program_offering', 'view', 'View College Program Offerings', false],
        ['college_program_offering.create', 'college_program_offering', 'create', 'Create College Program Offerings', false],
        ['college_program_offering.update', 'college_program_offering', 'update', 'Update College Program Offerings', false],
        ['college_program_offering.enable', 'college_program_offering', 'enable', 'Activate College Program Offerings', true],
        ['college_program_offering.disable', 'college_program_offering', 'disable', 'Deactivate College Program Offerings', true],
    ];

    public function up(): void
    {
        $now = now();

        foreach ($this->permissions as [$code, $resource, $action, $description, $sensitive]) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $code],
                [
                    'resource' => $resource,
                    'action' => $action,
                    'module' => 'College Academic Setup',
                    'description' => $description,
                    'is_sensitive' => $sensitive,
                    'is_college_delegable' => true,
                    'status' => 'ACTIVE',
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        $superAdminRoleId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');
        if ($superAdminRoleId) {
            $permissionIds = DB::table('permissions')
                ->whereIn('code', array_column($this->permissions, 0))
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $superAdminRoleId, 'permission_id' => $permissionId],
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
