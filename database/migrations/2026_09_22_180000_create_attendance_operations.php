<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        ['college_attendance.view', 'view', 'View Attendance Operations', false],
        ['college_attendance.manage', 'manage', 'Enter and save draft attendance', false],
        ['college_attendance.finalize', 'finalize', 'Finalize attendance registers', true],
        ['college_attendance.correct', 'correct', 'Reopen finalized attendance for correction', true],
    ];

    public function up(): void
    {
        Schema::create('attendance_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_schedule_id')->unique()->constrained('class_schedules')->restrictOnDelete();
            $table->foreignId('academic_policy_id')->constrained('academic_policies')->restrictOnDelete();
            $table->enum('status', ['DRAFT', 'FINALIZED'])->default('DRAFT');
            $table->unsignedInteger('revision_no')->default(0);
            $table->timestamp('finalized_at')->nullable();
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('correction_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['academic_policy_id', 'status'], 'attendance_register_policy_status_idx');
        });

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_register_id')->constrained('attendance_registers')->restrictOnDelete();
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->restrictOnDelete();
            $table->enum('attendance_status', ['PRESENT', 'ABSENT', 'LATE', 'EXCUSED']);
            $table->string('remarks', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['attendance_register_id', 'student_enrollment_id'], 'attendance_record_register_enrollment_uq');
            $table->index(['student_enrollment_id', 'attendance_status'], 'attendance_record_enrollment_status_idx');
        });

        $now = now();
        foreach ($this->permissions as [$code, $action, $description, $sensitive]) {
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'resource' => 'college_attendance', 'action' => $action, 'module' => 'Attendance Operations',
                'description' => $description, 'is_sensitive' => $sensitive, 'is_college_delegable' => true,
                'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }
        $ids = DB::table('permissions')->whereIn('code', array_column($this->permissions, 0))->pluck('id');
        foreach (['SUPER_ADMIN', 'COLLEGE_ADMIN'] as $roleCode) {
            $roleId = DB::table('roles')->where('code', $roleCode)->where('status', 'ACTIVE')->value('id');
            foreach ($roleId ? $ids : [] as $id) {
                DB::table('role_permissions')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $id], ['created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('attendance_registers');
        $ids = DB::table('permissions')->whereIn('code', array_column($this->permissions, 0))->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
