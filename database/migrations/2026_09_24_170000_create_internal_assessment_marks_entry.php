<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        ['college_internal_assessment.mid_semester', 'mid_semester', 'Create and manage Mid Semester activities', false],
        ['college_internal_assessment.practical', 'practical', 'Create and manage Practical activities', false],
        ['college_internal_assessment.marks_entry', 'marks_entry', 'Enter and correct Internal Assessment marks', true],
    ];

    public function up(): void
    {
        Schema::create('internal_assessment_marks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('internal_assessment_activity_student_id');
            $table->enum('result_status', ['ENTERED', 'ABSENT']);
            $table->decimal('marks_obtained', 8, 2)->nullable();
            $table->string('remarks', 500)->nullable();
            $table->unsignedInteger('revision_no')->default(0);
            $table->dateTime('entered_at');
            $table->foreignId('entered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique('internal_assessment_activity_student_id', 'internal_assessment_mark_student_uq');
            $table->foreign('internal_assessment_activity_student_id', 'ia_mark_activity_student_fk')->references('id')->on('internal_assessment_activity_students')->restrictOnDelete();
        });

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
        Schema::dropIfExists('internal_assessment_marks');
        $ids = DB::table('permissions')->whereIn('code', array_column($this->permissions, 0))->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
