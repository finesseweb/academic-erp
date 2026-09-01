<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('college_applicant_registration_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->unique()->constrained('colleges')->cascadeOnDelete();
            $table->boolean('registration_enabled')->default(true);
            $table->boolean('email_verification_required')->default(false);
            $table->boolean('captcha_required')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('applicant_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('college_id')->constrained('colleges')->restrictOnDelete();
            $table->date('date_of_birth');
            $table->string('phone', 40)->nullable();
            $table->enum('lifecycle_status', ['APPLICANT','STUDENT_ENABLED'])->default('APPLICANT');
            $table->unsignedBigInteger('student_id')->nullable()->comment('Future Student master link; populated only after approved admission conversion.');
            $table->timestamp('student_enabled_at')->nullable();
            $table->timestamps();
            $table->index(['college_id','lifecycle_status']);
        });
        Schema::table('college_admission_applications', function (Blueprint $table) {
            $table->foreignId('applicant_user_id')->nullable()->after('college_id')->constrained('users')->nullOnDelete();
            $table->index(['applicant_user_id','status'], 'caa_applicant_user_status_idx');
        });
    }
    public function down(): void {
        Schema::table('college_admission_applications', function (Blueprint $table) {
            $table->dropIndex('caa_applicant_user_status_idx');
            $table->dropConstrainedForeignId('applicant_user_id');
        });
        Schema::dropIfExists('applicant_profiles');
        Schema::dropIfExists('college_applicant_registration_settings');
    }
};
