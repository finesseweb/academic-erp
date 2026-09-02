<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('college_admission_merit_entries')) {
            return;
        }

        Schema::create('college_admission_merit_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_id');
            $table->unsignedBigInteger('college_program_intake_id');
            $table->string('bucket_key', 160);
            $table->unsignedBigInteger('college_admission_application_id');
            $table->unsignedBigInteger('college_admission_application_choice_id');
            $table->unsignedBigInteger('college_admission_score_id');
            $table->unsignedBigInteger('college_admission_selection_rule_id');
            $table->uuid('generation_batch');
            $table->unsignedInteger('rank');
            $table->decimal('final_weighted_score', 8, 3);
            $table->json('tie_break_snapshot')->nullable();
            $table->timestamp('generated_at');
            $table->unsignedBigInteger('generated_by');
            $table->timestamps();

            $table->foreign('college_id', 'came_college_fk')->references('id')->on('colleges')->restrictOnDelete();
            $table->foreign('college_program_intake_id', 'came_intake_fk')->references('id')->on('college_program_intakes')->restrictOnDelete();
            $table->foreign('college_admission_application_id', 'came_app_fk')->references('id')->on('college_admission_applications')->restrictOnDelete();
            $table->foreign('college_admission_application_choice_id', 'came_choice_fk')->references('id')->on('college_admission_application_choices')->restrictOnDelete();
            $table->foreign('college_admission_score_id', 'came_score_fk')->references('id')->on('college_admission_scores')->restrictOnDelete();
            $table->foreign('college_admission_selection_rule_id', 'came_rule_fk')->references('id')->on('college_admission_selection_rules')->restrictOnDelete();
            $table->foreign('generated_by', 'came_generated_by_fk')->references('id')->on('users')->restrictOnDelete();

            $table->unique('college_admission_application_choice_id', 'came_choice_uq');
            $table->unique(['college_admission_selection_rule_id', 'rank'], 'came_rule_rank_uq');
            $table->index(['college_id', 'college_program_intake_id', 'bucket_key'], 'came_scope_idx');
            $table->index(['college_admission_selection_rule_id', 'generation_batch'], 'came_rule_batch_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_merit_entries');
    }
};
