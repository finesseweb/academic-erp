<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Clean up remnants from a previous failed attempt of this same new migration.
        // The original version could create the table before MySQL rejected an overlong
        // automatically generated foreign-key identifier, leaving an unlogged partial table.
        Schema::dropIfExists('college_admission_application_course_choices');
        Schema::dropIfExists('college_admission_application_academic_preferences');

        Schema::create('college_admission_application_academic_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_application_id');
            $table->unique('college_admission_application_id', 'caa_pref_application_uq');
            $table->unsignedBigInteger('college_program_offering_id');
            $table->unsignedBigInteger('curriculum_id');
            $table->unsignedBigInteger('discipline_id')->nullable();
            $table->unsignedBigInteger('specialization_id')->nullable();
            $table->json('curriculum_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('college_admission_application_id', 'caa_pref_application_fk')
                ->references('id')->on('college_admission_applications')->cascadeOnDelete();
            $table->foreign('college_program_offering_id', 'caa_pref_offering_fk')
                ->references('id')->on('college_program_offerings')->restrictOnDelete();
            $table->foreign('curriculum_id', 'caa_pref_curriculum_fk')
                ->references('id')->on('curricula')->restrictOnDelete();
            $table->foreign('discipline_id', 'caa_pref_discipline_fk')
                ->references('id')->on('academic_disciplines')->restrictOnDelete();
            $table->foreign('specialization_id', 'caa_pref_specialization_fk')
                ->references('id')->on('academic_disciplines')->restrictOnDelete();
        });

        Schema::create('college_admission_application_course_choices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_application_id');
            $table->unsignedBigInteger('curriculum_term_id');
            $table->unsignedBigInteger('curriculum_slot_id');
            $table->unsignedBigInteger('curriculum_course_mapping_id');
            $table->unsignedBigInteger('course_id');
            $table->enum('selection_source', ['AUTO_MANDATORY', 'APPLICANT_CHOICE']);
            $table->timestamps();

            $table->foreign('college_admission_application_id', 'caa_choice_application_fk')
                ->references('id')->on('college_admission_applications')->cascadeOnDelete();
            $table->foreign('curriculum_term_id', 'caa_choice_term_fk')
                ->references('id')->on('curriculum_terms')->restrictOnDelete();
            $table->foreign('curriculum_slot_id', 'caa_choice_slot_fk')
                ->references('id')->on('curriculum_slots')->restrictOnDelete();
            $table->foreign('curriculum_course_mapping_id', 'caa_choice_mapping_fk')
                ->references('id')->on('curriculum_course_mappings')->restrictOnDelete();
            $table->foreign('course_id', 'caa_choice_course_fk')
                ->references('id')->on('courses')->restrictOnDelete();

            $table->unique(['college_admission_application_id', 'curriculum_course_mapping_id'], 'caa_course_mapping_uq');
            $table->index(['college_admission_application_id', 'selection_source'], 'caa_course_source_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_application_course_choices');
        Schema::dropIfExists('college_admission_application_academic_preferences');
    }
};
