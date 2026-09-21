<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        ['college_course_offering.view', 'view', 'View Course Offerings', false],
        ['college_course_offering.create', 'create', 'Create Course Offerings', false],
        ['college_course_offering.enable', 'enable', 'Activate Course Offerings', true],
        ['college_course_offering.disable', 'disable', 'Deactivate Course Offerings', true],
    ];

    public function up(): void
    {
        Schema::create('course_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('batches')->restrictOnDelete();
            $table->foreignId('curriculum_course_mapping_id')->constrained('curriculum_course_mappings')->restrictOnDelete();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('INACTIVE');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['batch_id', 'curriculum_course_mapping_id'], 'course_offerings_batch_mapping_uq');
            $table->index(['batch_id', 'status'], 'course_offerings_batch_status_idx');
        });

        $now = now();
        foreach ($this->permissions as [$code, $action, $description, $sensitive]) {
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'resource' => 'college_course_offering',
                'action' => $action,
                'module' => 'Course Delivery',
                'description' => $description,
                'is_sensitive' => $sensitive,
                'is_college_delegable' => true,
                'status' => 'ACTIVE',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $permissionIds = DB::table('permissions')->whereIn('code', array_column($this->permissions, 0))->pluck('id');
        foreach (['SUPER_ADMIN', 'COLLEGE_ADMIN'] as $roleCode) {
            $roleId = DB::table('roles')->where('code', $roleCode)->where('status', 'ACTIVE')->value('id');
            if (! $roleId) continue;
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
        $codes = array_column($this->permissions, 0);
        $permissionIds = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('code', $codes)->delete();
        Schema::dropIfExists('course_offerings');
    }
};
