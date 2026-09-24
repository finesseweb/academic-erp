<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $code = 'college_faculty_allocation.eligible';

    public function up(): void
    {
        DB::table('permissions')->updateOrInsert(
            ['code' => $this->code],
            [
                'resource' => 'college_faculty_allocation',
                'action' => 'eligible',
                'module' => 'Course Delivery',
                'description' => 'Eligible for Faculty Allocation',
                'is_sensitive' => false,
                'is_college_delegable' => true,
                'status' => 'ACTIVE',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        $permissionId = DB::table('permissions')->where('code', $this->code)->value('id');

        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }
    }
};
