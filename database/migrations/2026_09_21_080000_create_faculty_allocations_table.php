<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        ['college_faculty_allocation.view', 'view', 'View Faculty Allocations', false],
        ['college_faculty_allocation.create', 'create', 'Create Faculty Allocations', false],
        ['college_faculty_allocation.update', 'update', 'Update Faculty Allocations', false],
        ['college_faculty_allocation.enable', 'enable', 'Activate Faculty Allocations', true],
        ['college_faculty_allocation.disable', 'disable', 'Deactivate Faculty Allocations', true],
        ['college_faculty_allocation.eligible', 'eligible', 'Eligible for Faculty Allocation', false],
    ];

    public function up(): void
    {
        Schema::create('faculty_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_offering_id')->constrained('course_offerings')->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->restrictOnDelete();
            $table->foreignId('faculty_user_id')->constrained('users')->restrictOnDelete();
            $table->enum('teaching_role', ['PRIMARY', 'CO_FACULTY', 'PRACTICAL'])->default('PRIMARY');
            $table->decimal('weekly_load', 5, 2)->unsigned()->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('INACTIVE');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['course_offering_id', 'status'], 'faculty_allocations_offering_status_idx');
            $table->index(['faculty_user_id', 'status'], 'faculty_allocations_faculty_status_idx');
        });

        $now = now();
        foreach ($this->permissions as [$code, $action, $description, $sensitive]) {
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'resource' => 'college_faculty_allocation', 'action' => $action,
                'module' => 'Course Delivery', 'description' => $description,
                'is_sensitive' => $sensitive, 'is_college_delegable' => true,
                'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $ids = DB::table('permissions')->whereIn('code', array_column($this->permissions, 0))
            ->where('code', '!=', 'college_faculty_allocation.eligible')->pluck('id');
        foreach (['SUPER_ADMIN', 'COLLEGE_ADMIN'] as $roleCode) {
            $roleId = DB::table('roles')->where('code', $roleCode)->where('status', 'ACTIVE')->value('id');
            if (! $roleId) {
                continue;
            }
            foreach ($ids as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId], ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        $codes = array_column($this->permissions, 0);
        $ids = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('code', $codes)->delete();
        Schema::dropIfExists('faculty_allocations');
    }
};
