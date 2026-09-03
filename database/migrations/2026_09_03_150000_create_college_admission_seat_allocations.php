<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('college_admission_seat_allocations')) {
            Schema::create('college_admission_seat_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('college_id');
                $table->unsignedBigInteger('college_program_intake_id');
                $table->enum('bucket_type', ['PROGRAM', 'DISCIPLINE_GENERAL', 'SPECIALIZATION']);
                $table->string('bucket_key', 160);
                $table->unsignedBigInteger('college_program_reservation_plan_id')->nullable();
                $table->unsignedBigInteger('college_admission_merit_entry_id');
                $table->unsignedBigInteger('college_admission_application_id');
                $table->unsignedBigInteger('college_admission_application_choice_id');
                $table->unsignedBigInteger('college_admission_document_verification_id');
                $table->unsignedBigInteger('college_admission_score_id');
                $table->unsignedBigInteger('college_admission_selection_rule_id');
                $table->unsignedInteger('merit_rank');
                $table->decimal('final_weighted_score', 8, 3);
                $table->enum('physical_seat_type', ['OPEN', 'RESERVED']);
                $table->unsignedBigInteger('physical_reservation_category_id')->nullable();
                $table->string('physical_category_code', 40)->nullable();
                $table->string('physical_category_name', 120)->nullable();
                $table->unsignedSmallInteger('allocation_round')->default(1);
                $table->enum('status', ['ALLOCATED', 'CANCELLED'])->default('ALLOCATED');
                $table->text('decision_note')->nullable();
                $table->timestamp('allocated_at');
                $table->unsignedBigInteger('allocated_by');
                $table->timestamp('cancelled_at')->nullable();
                $table->unsignedBigInteger('cancelled_by')->nullable();
                $table->text('cancellation_reason')->nullable();
                $table->timestamps();

                $table->foreign('college_id', 'casa_college_fk')->references('id')->on('colleges')->restrictOnDelete();
                $table->foreign('college_program_intake_id', 'casa_intake_fk')->references('id')->on('college_program_intakes')->restrictOnDelete();
                $table->foreign('college_program_reservation_plan_id', 'casa_plan_fk')->references('id')->on('college_program_reservation_plans')->restrictOnDelete();
                $table->foreign('college_admission_merit_entry_id', 'casa_merit_fk')->references('id')->on('college_admission_merit_entries')->restrictOnDelete();
                $table->foreign('college_admission_application_id', 'casa_app_fk')->references('id')->on('college_admission_applications')->restrictOnDelete();
                $table->foreign('college_admission_application_choice_id', 'casa_choice_fk')->references('id')->on('college_admission_application_choices')->restrictOnDelete();
                $table->foreign('college_admission_document_verification_id', 'casa_doc_verification_fk')->references('id')->on('college_admission_document_verifications')->restrictOnDelete();
                $table->foreign('college_admission_score_id', 'casa_score_fk')->references('id')->on('college_admission_scores')->restrictOnDelete();
                $table->foreign('college_admission_selection_rule_id', 'casa_rule_fk')->references('id')->on('college_admission_selection_rules')->restrictOnDelete();
                $table->foreign('physical_reservation_category_id', 'casa_physical_category_fk')->references('id')->on('reservation_categories')->restrictOnDelete();
                $table->foreign('allocated_by', 'casa_allocated_by_fk')->references('id')->on('users')->restrictOnDelete();
                $table->foreign('cancelled_by', 'casa_cancelled_by_fk')->references('id')->on('users')->restrictOnDelete();

                $table->unique('college_admission_merit_entry_id', 'casa_merit_uq');
                $table->unique('college_admission_application_choice_id', 'casa_choice_uq');
                $table->index(['college_id', 'college_program_intake_id', 'bucket_key', 'status'], 'casa_bucket_status_idx');
                $table->index(['college_admission_application_id', 'status'], 'casa_application_status_idx');
                $table->index(['physical_reservation_category_id', 'status'], 'casa_category_status_idx');
                $table->index(['college_admission_selection_rule_id', 'status'], 'casa_rule_status_idx');
            });
        }

        if (! Schema::hasTable('college_admission_seat_allocation_horizontal_categories')) {
            Schema::create('college_admission_seat_allocation_horizontal_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('college_admission_seat_allocation_id');
                $table->unsignedBigInteger('reservation_category_id');
                $table->string('category_code', 40);
                $table->string('category_name', 120);
                $table->boolean('fulfills_target')->default(false);
                $table->unsignedBigInteger('created_by');
                $table->timestamps();

                $table->foreign('college_admission_seat_allocation_id', 'casah_allocation_fk')->references('id')->on('college_admission_seat_allocations')->cascadeOnDelete();
                $table->foreign('reservation_category_id', 'casah_category_fk')->references('id')->on('reservation_categories')->restrictOnDelete();
                $table->foreign('created_by', 'casah_created_by_fk')->references('id')->on('users')->restrictOnDelete();

                $table->unique(['college_admission_seat_allocation_id', 'reservation_category_id'], 'casah_allocation_category_uq');
                $table->index(['reservation_category_id', 'fulfills_target'], 'casah_category_target_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_seat_allocation_horizontal_categories');
        Schema::dropIfExists('college_admission_seat_allocations');
    }
};
