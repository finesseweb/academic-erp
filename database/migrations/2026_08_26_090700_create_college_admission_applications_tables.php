<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * MySQL can leave a CREATE TABLE migration partially applied when a later
         * ALTER that adds a foreign key fails. Because a failed Laravel migration
         * is not written to the migrations table, this file must be safe to rerun.
         * These tables belong only to this migration, so a leftover copy means the
         * previous attempt did not complete and is safe to recreate from scratch.
         */
        if (Schema::hasTable('college_admission_application_choices')) {
            Schema::drop('college_admission_application_choices');
        }

        if (Schema::hasTable('college_admission_applications')) {
            Schema::drop('college_admission_applications');
        }

        Schema::create('college_admission_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_id');
            $table->unsignedBigInteger('college_admission_cycle_id');
            $table->string('application_no', 90);
            $table->string('external_reference', 120)->nullable();
            $table->string('candidate_name', 180);
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->date('date_of_birth');
            $table->enum('status', ['DRAFT', 'SUBMITTED', 'WITHDRAWN'])->default('DRAFT');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('college_id', 'caa_college_fk')
                ->references('id')->on('colleges')->restrictOnDelete();
            $table->foreign('college_admission_cycle_id', 'caa_cycle_fk')
                ->references('id')->on('college_admission_cycles')->restrictOnDelete();
            $table->foreign('created_by', 'caa_created_by_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'caa_updated_by_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->unique(['college_id', 'application_no'], 'caa_college_application_no_uq');
            $table->index(['college_id', 'college_admission_cycle_id', 'status'], 'caa_scope_cycle_status_idx');
            $table->index(['college_id', 'candidate_name'], 'caa_scope_candidate_idx');
        });

        Schema::create('college_admission_application_choices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_application_id');
            $table->unsignedSmallInteger('preference_no');
            $table->unsignedBigInteger('college_program_intake_id');
            $table->unsignedBigInteger('college_program_reservation_plan_id')->nullable();
            $table->unsignedBigInteger('college_admission_selection_rule_id')->nullable();
            $table->enum('bucket_type', ['PROGRAM', 'DISCIPLINE_GENERAL', 'SPECIALIZATION']);
            $table->string('bucket_key', 80);
            $table->unsignedInteger('basis_capacity');
            $table->enum('eligibility_status', ['PENDING', 'ELIGIBLE', 'INELIGIBLE'])->default('PENDING');
            $table->text('eligibility_reason')->nullable();
            $table->timestamp('eligibility_checked_at')->nullable();
            $table->unsignedBigInteger('eligibility_checked_by')->nullable();
            $table->timestamps();

            $table->foreign('college_admission_application_id', 'caac_application_fk')
                ->references('id')->on('college_admission_applications')->cascadeOnDelete();
            $table->foreign('college_program_intake_id', 'caac_intake_fk')
                ->references('id')->on('college_program_intakes')->restrictOnDelete();
            $table->foreign('college_program_reservation_plan_id', 'caac_reservation_plan_fk')
                ->references('id')->on('college_program_reservation_plans')->restrictOnDelete();
            $table->foreign('college_admission_selection_rule_id', 'caac_selection_rule_fk')
                ->references('id')->on('college_admission_selection_rules')->restrictOnDelete();
            $table->foreign('eligibility_checked_by', 'caac_eligibility_user_fk')
                ->references('id')->on('users')->nullOnDelete();

            $table->unique(['college_admission_application_id', 'preference_no'], 'caac_application_preference_uq');
            $table->unique(['college_admission_application_id', 'college_program_intake_id', 'bucket_key'], 'caac_application_bucket_uq');
            $table->index(['college_admission_selection_rule_id', 'eligibility_status'], 'caac_rule_eligibility_idx');
            $table->index(['college_program_reservation_plan_id', 'eligibility_status'], 'caac_plan_eligibility_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_application_choices');
        Schema::dropIfExists('college_admission_applications');
    }
};
