<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('college_admission_form_fields', function (Blueprint $table) {
            $table->enum('condition_match_mode', ['ALL', 'ANY'])->default('ALL')->after('visibility_rules');
        });

        Schema::create('college_admission_form_field_conditions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_form_field_id');
            $table->unsignedBigInteger('source_field_id');
            $table->enum('operator', ['EQUALS','NOT_EQUALS','IN','NOT_IN','CONTAINS','IS_EMPTY','IS_NOT_EMPTY'])->default('EQUALS');
            $table->json('compare_values')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('college_admission_form_field_id', 'caffc_target_fk')->references('id')->on('college_admission_form_fields')->cascadeOnDelete();
            $table->foreign('source_field_id', 'caffc_source_fk')->references('id')->on('college_admission_form_fields')->restrictOnDelete();
            $table->index(['college_admission_form_field_id','is_active','display_order'], 'caffc_target_active_idx');
        });

        Schema::create('college_admission_form_field_scopes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_form_field_id');
            $table->unsignedBigInteger('degree_level_id')->nullable();
            $table->unsignedBigInteger('degree_id')->nullable();
            $table->unsignedBigInteger('program_template_id')->nullable();
            $table->unsignedBigInteger('college_program_offering_id')->nullable();
            $table->unsignedBigInteger('curriculum_id')->nullable();
            $table->unsignedBigInteger('college_admission_cycle_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('college_admission_form_field_id', 'caffs_field_fk')->references('id')->on('college_admission_form_fields')->cascadeOnDelete();
            $table->foreign('degree_level_id', 'caffs_degree_level_fk')->references('id')->on('degree_levels')->restrictOnDelete();
            $table->foreign('degree_id', 'caffs_degree_fk')->references('id')->on('degrees')->restrictOnDelete();
            $table->foreign('program_template_id', 'caffs_program_fk')->references('id')->on('program_templates')->restrictOnDelete();
            $table->foreign('college_program_offering_id', 'caffs_offering_fk')->references('id')->on('college_program_offerings')->restrictOnDelete();
            $table->foreign('curriculum_id', 'caffs_curriculum_fk')->references('id')->on('curricula')->restrictOnDelete();
            $table->foreign('college_admission_cycle_id', 'caffs_cycle_fk')->references('id')->on('college_admission_cycles')->restrictOnDelete();
            $table->index(['college_admission_form_field_id','is_active'], 'caffs_field_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_form_field_scopes');
        Schema::dropIfExists('college_admission_form_field_conditions');
        Schema::table('college_admission_form_fields', function (Blueprint $table) {
            $table->dropColumn('condition_match_mode');
        });
    }
};
