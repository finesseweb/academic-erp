<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $permissions = [
        ['academic_calendar.view', 'academic_calendar', 'view', 'View University Academic Calendar', false],
        ['academic_calendar.create', 'academic_calendar', 'create', 'Create University Academic Calendar', false],
        ['academic_calendar.update', 'academic_calendar', 'update', 'Update University Academic Calendar', false],
        ['academic_calendar.disable', 'academic_calendar', 'disable', 'Enable or disable University Academic Calendar', true],
        ['academic_calendar.event_create', 'academic_calendar', 'event_create', 'Add University Academic Calendar events', false],
        ['academic_calendar.event_update', 'academic_calendar', 'event_update', 'Update University Academic Calendar events', false],
        ['academic_calendar.event_disable', 'academic_calendar', 'event_disable', 'Enable or disable University Academic Calendar events', true],
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
                    'module' => 'Academic Calendar',
                    'description' => $description,
                    'is_sensitive' => $sensitive,
                    'is_college_delegable' => false,
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
