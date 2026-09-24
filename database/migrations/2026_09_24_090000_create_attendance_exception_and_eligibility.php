<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        ['college_attendance_exception.view', 'view', 'View attendance exception requests', false],
        ['college_attendance_exception.request', 'request', 'Create attendance condonation or exemption requests', false],
        ['college_attendance_exception.decide', 'decide', 'Approve or reject attendance exception requests', true],
        ['college_attendance_eligibility.view', 'view', 'View final attendance examination eligibility', false],
        ['college_attendance_eligibility.finalize', 'finalize', 'Finalize attendance examination eligibility', true],
    ];

    public function up(): void
    {
        Schema::create('attendance_exception_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained('colleges')->restrictOnDelete();
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->restrictOnDelete();
            $table->foreignId('course_offering_id')->constrained('course_offerings')->restrictOnDelete();
            $table->foreignId('academic_policy_id')->constrained('academic_policies')->restrictOnDelete();
            $table->enum('type', ['CONDONATION', 'SPECIAL_EXEMPTION']);
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING');
            $table->decimal('attendance_percent_snapshot', 5, 2);
            $table->text('reason');
            $table->string('supporting_reference', 500)->nullable();
            $table->text('decision_remarks')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_enrollment_id', 'course_offering_id', 'type', 'status'], 'attendance_exception_subject_idx');
            $table->index(['college_id', 'status', 'type'], 'attendance_exception_queue_idx');
        });

        Schema::create('student_attendance_eligibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained('colleges')->restrictOnDelete();
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->restrictOnDelete();
            $table->foreignId('course_offering_id')->constrained('course_offerings')->restrictOnDelete();
            $table->foreignId('academic_policy_id')->constrained('academic_policies')->restrictOnDelete();
            $table->unsignedInteger('classes_held');
            $table->unsignedInteger('classes_attended');
            $table->decimal('attendance_percent', 5, 2);
            $table->enum('basis', ['NOT_REQUIRED', 'NORMAL', 'CONDONATION', 'SPECIAL_EXEMPTION', 'SHORTAGE']);
            $table->boolean('is_eligible');
            $table->timestamp('finalized_at');
            $table->foreignId('finalized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['student_enrollment_id', 'course_offering_id'], 'student_attendance_eligibility_uq');
            $table->index(['college_id', 'is_eligible'], 'attendance_eligibility_college_result_idx');
        });

        $now = now();
        foreach ($this->permissions as [$code, $action, $description, $sensitive]) {
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'resource' => str_contains($code, 'exception') ? 'college_attendance_exception' : 'college_attendance_eligibility',
                'action' => $action, 'module' => 'Attendance Operations', 'description' => $description,
                'is_sensitive' => $sensitive, 'is_college_delegable' => true, 'status' => 'ACTIVE',
                'created_at' => $now, 'updated_at' => $now,
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
        Schema::dropIfExists('student_attendance_eligibilities');
        Schema::dropIfExists('attendance_exception_requests');
        $ids = DB::table('permissions')->whereIn('code', array_column($this->permissions, 0))->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
