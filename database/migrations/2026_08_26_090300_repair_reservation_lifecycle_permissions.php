<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $definitions = [
            ['college_reservation.enable', 'enable', 'Activate College Reservation / Seat Distribution'],
            ['college_reservation.disable', 'disable', 'Deactivate College Reservation / Seat Distribution'],
        ];

        foreach ($definitions as [$code, $action, $description]) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $code],
                [
                    'resource' => 'college_reservation',
                    'action' => $action,
                    'module' => 'College Academic Setup',
                    'description' => $description,
                    'is_sensitive' => true,
                    'is_college_delegable' => true,
                    'status' => 'ACTIVE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('code', array_column($definitions, 0))
            ->pluck('id');

        foreach (['COLLEGE_ADMIN', 'SUPER_ADMIN'] as $roleCode) {
            $roleId = DB::table('roles')->where('code', $roleCode)->value('id');
            if (! $roleId) {
                continue;
            }

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
        // Protected-role lifecycle permission repair is intentionally non-destructive.
    }
};
