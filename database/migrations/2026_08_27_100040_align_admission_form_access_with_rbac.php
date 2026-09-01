<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $codes = [
        'college_admission_form.view',
        'college_admission_form.manage',
        'college_admission_form.map',
        'college_application_fee.manage',
    ];

    public function up(): void
    {
        // Admission Form access follows the ERP's existing RBAC hierarchy.
        // Remove the temporary parallel University->College feature/role gate tables.
        Schema::dropIfExists('college_admission_form_access_roles');
        Schema::dropIfExists('college_admission_form_access_controls');

        DB::table('permissions')->whereIn('code', $this->codes)->update([
            'module' => 'Admission',
            'is_college_delegable' => true,
            'status' => 'ACTIVE',
            'updated_at' => now(),
        ]);

        $permissionIds = DB::table('permissions')->whereIn('code', $this->codes)->pluck('id');

        // University/Super Admin owns the feature by default.
        $superAdminId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');
        if ($superAdminId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $superAdminId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }

        // College access must be explicitly delegated through Roles -> Permissions,
        // exactly like other College-scoped ERP capabilities.
        $collegeAdminId = DB::table('roles')->where('code', 'COLLEGE_ADMIN')->value('id');
        if ($collegeAdminId) {
            DB::table('role_permissions')
                ->where('role_id', $collegeAdminId)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }
    }

    public function down(): void
    {
        // Intentionally do not recreate the deprecated parallel gate tables.
        // RBAC permission assignments are preserved on rollback.
    }
};
