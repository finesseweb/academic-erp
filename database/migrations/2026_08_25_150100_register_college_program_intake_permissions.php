<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        ['college_program_intake.view', 'college_program_intake', 'view', 'View College Intake / Seat Capacity', false],
        ['college_program_intake.create', 'college_program_intake', 'create', 'Create College Intake / Seat Capacity', false],
        ['college_program_intake.update', 'college_program_intake', 'update', 'Update College Intake / Seat Capacity and allocations', false],
        ['college_program_intake.enable', 'college_program_intake', 'enable', 'Activate College Intake / Seat Capacity', true],
        ['college_program_intake.disable', 'college_program_intake', 'disable', 'Deactivate College Intake / Seat Capacity', true],
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

        $permissionIds = DB::table('permissions')
            ->whereIn('code', array_column($this->permissions, 0))
            ->pluck('id');

        foreach (['SUPER_ADMIN', 'COLLEGE_ADMIN'] as $roleCode) {
            $roleId = DB::table('roles')->where('code', $roleCode)->value('id');

            if (! $roleId) {
                continue;
            }

            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ],
                    [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    public function down(): void
    {
        $codes = array_column($this->permissions, 0);
        $permissionIds = DB::table('permissions')
            ->whereIn('code', $codes)
            ->pluck('id');

        DB::table('role_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('code', $codes)
            ->delete();
    }
};
