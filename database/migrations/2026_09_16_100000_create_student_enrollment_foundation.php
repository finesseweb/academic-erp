<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained('colleges')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('admission_id')->nullable()->unique()->constrained('admissions')->restrictOnDelete();
            $table->foreignId('college_admission_application_id')->nullable()->unique()->constrained('college_admission_applications')->restrictOnDelete();
            $table->enum('source_type', ['ADMISSION', 'IMPORT'])->default('ADMISSION');
            $table->string('student_uid', 100)->nullable();
            $table->string('full_name', 180);
            $table->date('date_of_birth')->nullable();
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['college_id', 'student_uid'], 'students_college_uid_uq');
            $table->index(['college_id', 'status'], 'students_college_status_idx');
            $table->index(['college_id', 'full_name'], 'students_college_name_idx');
        });

        Schema::table('applicant_profiles', function (Blueprint $table) {
            $table->foreign('student_id', 'applicant_profiles_student_fk')
                ->references('id')->on('students')->restrictOnDelete();
        });

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->foreignId('college_id')->constrained('colleges')->restrictOnDelete();
            $table->foreignId('college_program_offering_id')->constrained('college_program_offerings')->restrictOnDelete();
            $table->foreignId('admission_id')->nullable()->unique()->constrained('admissions')->restrictOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained('sections')->restrictOnDelete();
            $table->enum('source_type', ['ADMISSION', 'IMPORT'])->default('ADMISSION');
            $table->enum('status', ['ENROLLED', 'CANCELLED'])->default('ENROLLED');
            $table->timestamp('enrolled_at');
            $table->foreignId('enrolled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'college_program_offering_id'], 'student_enrollment_offering_uq');
            $table->index(['college_id', 'college_program_offering_id', 'status'], 'student_enrollment_scope_idx');
        });

        Schema::create('student_profile_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('source_application_field_id')->nullable()->constrained('college_admission_form_fields')->nullOnDelete();
            $table->string('profile_key', 120);
            $table->string('label_snapshot', 180);
            $table->text('value_text')->nullable();
            $table->json('value_json')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('file_mime', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'profile_key'], 'student_profile_key_uq');
            $table->index(['student_id', 'source_application_field_id'], 'student_profile_source_idx');
        });

        Schema::table('college_admission_form_fields', function (Blueprint $table) {
            $table->enum('student_data_policy', ['APPLICATION_ONLY', 'STUDENT_PROFILE'])
                ->default('APPLICATION_ONLY')
                ->after('system_purpose');
            $table->string('student_profile_key', 120)->nullable()->after('student_data_policy');
            $table->index(['student_data_policy', 'student_profile_key'], 'caff_student_mapping_idx');
        });
    }

    public function down(): void
    {
        Schema::table('college_admission_form_fields', function (Blueprint $table) {
            $table->dropIndex('caff_student_mapping_idx');
            $table->dropColumn(['student_data_policy', 'student_profile_key']);
        });

        Schema::table('applicant_profiles', function (Blueprint $table) {
            $table->dropForeign('applicant_profiles_student_fk');
        });

        Schema::dropIfExists('student_profile_values');
        Schema::dropIfExists('student_enrollments');
        Schema::dropIfExists('students');
    }
};
