<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();
        $permissions = [
            ['fee_category.view', 'fee_category', 'view', 'View University Fee Categories', false, false],
            ['fee_category.create', 'fee_category', 'create', 'Create University Fee Categories', false, false],
            ['fee_category.update', 'fee_category', 'update', 'Update University Fee Categories', false, false],
            ['fee_category.enable', 'fee_category', 'enable', 'Activate University Fee Categories', true, false],
            ['fee_category.disable', 'fee_category', 'disable', 'Deactivate University Fee Categories', true, false],
            ['college_fee_category.view', 'college_fee_category', 'view', 'View College Fee Categories', false, true],
            ['college_fee_category.create', 'college_fee_category', 'create', 'Create College Fee Categories', false, true],
            ['college_fee_category.update', 'college_fee_category', 'update', 'Update College Fee Categories', false, true],
            ['college_fee_category.enable', 'college_fee_category', 'enable', 'Activate College Fee Categories', true, true],
            ['college_fee_category.disable', 'college_fee_category', 'disable', 'Deactivate College Fee Categories', true, true],
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
        $codes = [
            'fee_category.view','fee_category.create','fee_category.update','fee_category.enable','fee_category.disable',
            'college_fee_category.view','college_fee_category.create','college_fee_category.update','college_fee_category.enable','college_fee_category.disable',
        ];
        $ids = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
