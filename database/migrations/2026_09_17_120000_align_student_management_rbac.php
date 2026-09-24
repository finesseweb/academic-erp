<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $now = now();

        // Enrollment and Identity are children of the same business/RBAC module.
        DB::table('permissions')->whereIn('code', [
            'college_student_enrollment.view',
            'college_student_enrollment.enroll',
            'college_student_identity.manage',
        ])->update(['module' => 'Student Management', 'updated_at' => $now]);

        DB::table('permissions')->updateOrInsert(
            ['code' => 'college_student_identity.view'],
            [
                'module' => 'Student Management',
                'resource' => 'college_student_identity',
                'action' => 'view',
                'description' => 'View Student Identity assignments and numbering rules',
                'is_sensitive' => false,
                'is_college_delegable' => true,
                'status' => 'ACTIVE',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $viewId = DB::table('permissions')->where('code', 'college_student_identity.view')->value('id');
        foreach (['SUPER_ADMIN', 'COLLEGE_ADMIN'] as $code) {
            $roleId = DB::table('roles')->where('code', $code)->where('status', 'ACTIVE')->value('id');
            if ($roleId && $viewId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $viewId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }

        // Anyone already delegated Identity Manage must retain page visibility after the split.
        $manageId = DB::table('permissions')->where('code', 'college_student_identity.manage')->value('id');
        if ($viewId && $manageId) {
            $roleIds = DB::table('role_permissions')->where('permission_id', $manageId)->pluck('role_id');
            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $viewId],
                    ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $viewId = DB::table('permissions')->where('code', 'college_student_identity.view')->value('id');
        if ($viewId) DB::table('role_permissions')->where('permission_id', $viewId)->delete();
        DB::table('permissions')->where('code', 'college_student_identity.view')->delete();
        DB::table('permissions')->whereIn('code', [
            'college_student_enrollment.view',
            'college_student_enrollment.enroll',
        ])->update(['module' => 'Student Enrollment', 'updated_at' => now()]);
    }
};
