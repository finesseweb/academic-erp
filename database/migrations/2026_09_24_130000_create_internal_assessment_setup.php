<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        ['college_internal_assessment.view', 'view', 'View Internal Assessment setup and activities', false],
        ['college_internal_assessment.setup', 'setup', 'Configure Internal Assessment components', true],
        ['college_internal_assessment.assignment', 'assignment', 'Create and manage Assignment activities', false],
        ['college_internal_assessment.quiz', 'quiz', 'Create and manage Quiz activities', false],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('internal_assessment_components')) {
            Schema::create('internal_assessment_components', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_offering_id')->constrained('course_offerings')->restrictOnDelete();
                $table->foreignId('academic_policy_id')->constrained('academic_policies')->restrictOnDelete();
                $table->enum('component_type', ['ASSIGNMENT', 'QUIZ', 'MID_SEMESTER', 'PRACTICAL', 'OTHER']);
                $table->string('name', 150);
                $table->decimal('maximum_marks', 8, 2);
                $table->decimal('weightage_percent', 5, 2);
                $table->decimal('minimum_pass_marks', 8, 2)->nullable();
                $table->unsignedInteger('display_order')->default(1);
                $table->enum('status', ['DRAFT', 'ACTIVE', 'INACTIVE'])->default('DRAFT');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['course_offering_id', 'name'], 'internal_assessment_component_name_uq');
                $table->index(['course_offering_id', 'component_type', 'status'], 'internal_assessment_component_lookup_idx');
            });
        }

        if (! Schema::hasTable('internal_assessment_activities')) {
            Schema::create('internal_assessment_activities', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('internal_assessment_component_id');
                $table->unsignedBigInteger('faculty_allocation_id');
                $table->string('title', 200);
                $table->text('instructions')->nullable();
                $table->dateTime('opens_at');
                $table->dateTime('closes_at');
                $table->unsignedInteger('duration_minutes')->nullable();
                $table->enum('status', ['DRAFT', 'PUBLISHED', 'CLOSED'])->default('DRAFT');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['internal_assessment_component_id', 'title'], 'internal_assessment_activity_title_uq');
                $table->index(['faculty_allocation_id', 'status', 'opens_at'], 'internal_assessment_activity_schedule_idx');
                $table->foreign('internal_assessment_component_id', 'ia_activity_component_fk')->references('id')->on('internal_assessment_components')->restrictOnDelete();
                $table->foreign('faculty_allocation_id', 'ia_activity_faculty_fk')->references('id')->on('faculty_allocations')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('internal_assessment_activity_students')) {
            Schema::create('internal_assessment_activity_students', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('internal_assessment_activity_id');
                $table->unsignedBigInteger('student_enrollment_id');
                $table->dateTime('assigned_at');
                $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->unique(['internal_assessment_activity_id', 'student_enrollment_id'], 'internal_assessment_activity_student_uq');
                $table->index('student_enrollment_id', 'internal_assessment_activity_student_enrollment_idx');
                $table->foreign('internal_assessment_activity_id', 'ia_student_activity_fk')->references('id')->on('internal_assessment_activities')->restrictOnDelete();
                $table->foreign('student_enrollment_id', 'ia_student_enrollment_fk')->references('id')->on('student_enrollments')->restrictOnDelete();
            });
        }

        $now = now();
        foreach ($this->permissions as [$code, $action, $description, $sensitive]) {
            DB::table('permissions')->updateOrInsert(['code' => $code], ['resource' => 'college_internal_assessment', 'action' => $action, 'module' => 'Internal Assessment', 'description' => $description, 'is_sensitive' => $sensitive, 'is_college_delegable' => true, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now]);
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
        Schema::dropIfExists('internal_assessment_activity_students');
        Schema::dropIfExists('internal_assessment_activities');
        Schema::dropIfExists('internal_assessment_components');
        $ids = DB::table('permissions')->whereIn('code', array_column($this->permissions, 0))->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
