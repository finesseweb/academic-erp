<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('admissions')) {
            return;
        }

        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_id');
            $table->unsignedBigInteger('college_program_intake_id');
            $table->unsignedBigInteger('college_program_reservation_plan_id')->nullable();
            $table->unsignedBigInteger('curriculum_id')->nullable();
            $table->unsignedBigInteger('college_admission_application_id');
            $table->unsignedBigInteger('college_admission_application_choice_id');
            $table->unsignedBigInteger('college_admission_document_verification_id');
            $table->unsignedBigInteger('college_admission_seat_allocation_id');
            // Nullable for the documented DIRECT-admission path, which may bypass Score/Merit/Selection Rule.
            // The current Seat Allocation implementation supplies all three for REGULAR admission.
            $table->unsignedBigInteger('college_admission_merit_entry_id')->nullable();
            $table->unsignedBigInteger('college_admission_score_id')->nullable();
            $table->unsignedBigInteger('college_admission_selection_rule_id')->nullable();
            $table->string('admission_no', 100);
            $table->enum('status', ['CONFIRMED', 'REVOKED'])->default('CONFIRMED');
            $table->text('decision_note')->nullable();
            $table->timestamp('confirmed_at');
            $table->unsignedBigInteger('confirmed_by');
            $table->timestamp('revoked_at')->nullable();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();

            $table->foreign('college_id', 'adm_college_fk')->references('id')->on('colleges')->restrictOnDelete();
            $table->foreign('college_program_intake_id', 'adm_intake_fk')->references('id')->on('college_program_intakes')->restrictOnDelete();
            $table->foreign('college_program_reservation_plan_id', 'adm_reservation_plan_fk')->references('id')->on('college_program_reservation_plans')->restrictOnDelete();
            $table->foreign('curriculum_id', 'adm_curriculum_fk')->references('id')->on('curricula')->restrictOnDelete();
            $table->foreign('college_admission_application_id', 'adm_application_fk')->references('id')->on('college_admission_applications')->restrictOnDelete();
            $table->foreign('college_admission_application_choice_id', 'adm_choice_fk')->references('id')->on('college_admission_application_choices')->restrictOnDelete();
            $table->foreign('college_admission_document_verification_id', 'adm_verification_fk')->references('id')->on('college_admission_document_verifications')->restrictOnDelete();
            $table->foreign('college_admission_seat_allocation_id', 'adm_seat_allocation_fk')->references('id')->on('college_admission_seat_allocations')->restrictOnDelete();
            $table->foreign('college_admission_merit_entry_id', 'adm_merit_fk')->references('id')->on('college_admission_merit_entries')->restrictOnDelete();
            $table->foreign('college_admission_score_id', 'adm_score_fk')->references('id')->on('college_admission_scores')->restrictOnDelete();
            $table->foreign('college_admission_selection_rule_id', 'adm_rule_fk')->references('id')->on('college_admission_selection_rules')->restrictOnDelete();
            $table->foreign('confirmed_by', 'adm_confirmed_by_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('revoked_by', 'adm_revoked_by_fk')->references('id')->on('users')->restrictOnDelete();

            $table->unique('admission_no', 'adm_admission_no_uq');
            $table->unique('college_admission_application_id', 'adm_application_uq');
            $table->unique('college_admission_seat_allocation_id', 'adm_seat_allocation_uq');
            $table->index(['college_id', 'status'], 'adm_college_status_idx');
            $table->index(['college_program_intake_id', 'status'], 'adm_intake_status_idx');
            $table->index(['college_admission_selection_rule_id', 'status'], 'adm_rule_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admissions');
    }
};
