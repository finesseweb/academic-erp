<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            ['fee_head.view', 'fee_head', 'view', 'View University Fee Heads', false, false],
            ['fee_head.create', 'fee_head', 'create', 'Create University Fee Heads', false, false],
            ['fee_head.update', 'fee_head', 'update', 'Update University Fee Heads', false, false],
            ['fee_head.enable', 'fee_head', 'enable', 'Activate University Fee Heads', true, false],
            ['fee_head.disable', 'fee_head', 'disable', 'Deactivate University Fee Heads', true, false],
            ['fee_structure.view', 'fee_structure', 'view', 'View University Fee Structures', false, false],
            ['fee_structure.create', 'fee_structure', 'create', 'Create University Fee Structures', false, false],
            ['fee_structure.update', 'fee_structure', 'update', 'Update University Fee Structures', false, false],
            ['fee_structure.enable', 'fee_structure', 'enable', 'Activate University Fee Structures', true, false],
            ['fee_structure.disable', 'fee_structure', 'disable', 'Deactivate University Fee Structures', true, false],
            ['college_fee_head.view', 'college_fee_head', 'view', 'View College Fee Heads', false, true],
            ['college_fee_head.create', 'college_fee_head', 'create', 'Create College Fee Heads', false, true],
            ['college_fee_head.update', 'college_fee_head', 'update', 'Update College Fee Heads', false, true],
            ['college_fee_head.enable', 'college_fee_head', 'enable', 'Activate College Fee Heads', true, true],
            ['college_fee_head.disable', 'college_fee_head', 'disable', 'Deactivate College Fee Heads', true, true],
            ['college_fee_structure.view', 'college_fee_structure', 'view', 'View College Fee Structures', false, true],
            ['college_fee_structure.create', 'college_fee_structure', 'create', 'Create College Fee Structures', false, true],
            ['college_fee_structure.update', 'college_fee_structure', 'update', 'Update College Fee Structures', false, true],
            ['college_fee_structure.enable', 'college_fee_structure', 'enable', 'Activate College Fee Structures', true, true],
            ['college_fee_structure.disable', 'college_fee_structure', 'disable', 'Deactivate College Fee Structures', true, true],
        ];

        foreach ($permissions as [$code, $resource, $action, $description, $sensitive, $delegable]) {
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'module' => 'Fee Management', 'resource' => $resource, 'action' => $action,
                'description' => $description, 'is_sensitive' => $sensitive,
                'is_college_delegable' => $delegable, 'status' => 'ACTIVE',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $superId = DB::table('roles')->where('code', 'SUPER_ADMIN')->where('status', 'ACTIVE')->value('id');
        if ($superId) {
            foreach (DB::table('permissions')->whereIn('code', array_column($permissions, 0))->pluck('id') as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(['role_id' => $superId, 'permission_id' => $permissionId], ['created_at' => $now, 'updated_at' => $now]);
            }
        }

        $collegeAdminId = DB::table('roles')->where('code', 'COLLEGE_ADMIN')->where('status', 'ACTIVE')->value('id');
        if ($collegeAdminId) {
            $codes = array_values(array_filter(array_column($permissions, 0), fn ($code) => str_starts_with($code, 'college_')));
            foreach (DB::table('permissions')->whereIn('code', $codes)->pluck('id') as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(['role_id' => $collegeAdminId, 'permission_id' => $permissionId], ['created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $codes = DB::table('permissions')->where('module', 'Fee Management')->pluck('code');
        $ids = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
