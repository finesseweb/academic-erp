<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        [
            'college_program_intake.view',
            'college_program_intake',
            'view',
            'View College Intake / Seat Capacity',
            false,
        ],
        [
            'college_program_intake.create',
            'college_program_intake',
            'create',
            'Create College Intake / Seat Capacity',
            false,
        ],
        [
            'college_program_intake.update',
            'college_program_intake',
            'update',
            'Update College Intake / Seat Capacity and allocations',
            false,
        ],
        [
            'college_program_intake.enable',
            'college_program_intake',
            'enable',
            'Activate College Intake / Seat Capacity',
            true,
        ],
        [
            'college_program_intake.disable',
            'college_program_intake',
            'disable',
            'Deactivate College Intake / Seat Capacity',
            true,
        ],
    ];

    public function up(): void
    {
        $now = now();

        foreach (
            $this->permissions as
            [$code, $resource, $action, $description, $sensitive]
        ) {
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
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $permissionIds = DB::table('permissions')
            ->whereIn(
                'code',
                array_column($this->permissions, 0)
            )
            ->pluck('id');

        /*
         * Protected system roles must always contain the mandatory
         * College Intake lifecycle permissions. Role assignment scope
         * is still enforced separately by user_roles.
         */
        foreach (
            ['SUPER_ADMIN', 'COLLEGE_ADMIN'] as $roleCode
        ) {
            $roleId = DB::table('roles')
                ->where('code', $roleCode)
                ->value('id');

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
        /*
         * Intentionally non-destructive.
         * This is a protected-role permission synchronization repair.
         * Rolling it back must not remove mandatory lifecycle access.
         */
    }
};
