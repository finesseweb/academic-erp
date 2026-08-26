<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('college_admission_scores')) {
            return;
        }

        Schema::create('college_admission_scores', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_application_id');
            $table->unsignedBigInteger('college_admission_application_choice_id');
            $table->unsignedBigInteger('college_admission_selection_rule_id');

            $table->decimal('merit_raw_score', 10, 3)->nullable();
            $table->decimal('merit_max_score', 10, 3)->nullable();
            $table->decimal('merit_normalized_score', 8, 3)->nullable();
            $table->decimal('entrance_raw_score', 10, 3)->nullable();
            $table->decimal('entrance_max_score', 10, 3)->nullable();
            $table->decimal('entrance_normalized_score', 8, 3)->nullable();
            $table->decimal('interview_normalized_score', 8, 3)->nullable();
            $table->decimal('final_weighted_score', 8, 3)->nullable();

            $table->enum('qualification_status', ['PENDING_INTERVIEW', 'QUALIFIED', 'NOT_QUALIFIED'])->default('QUALIFIED');
            $table->text('qualification_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('scored_at')->nullable();
            $table->unsignedBigInteger('scored_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('college_admission_application_id', 'cas_application_fk')
                ->references('id')->on('college_admission_applications')->cascadeOnDelete();
            $table->foreign('college_admission_application_choice_id', 'cas_choice_fk')
                ->references('id')->on('college_admission_application_choices')->cascadeOnDelete();
            $table->foreign('college_admission_selection_rule_id', 'cas_rule_fk')
                ->references('id')->on('college_admission_selection_rules')->restrictOnDelete();
            $table->foreign('scored_by', 'cas_scored_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'cas_updated_by_fk')->references('id')->on('users')->nullOnDelete();

            $table->unique('college_admission_application_choice_id', 'cas_choice_uq');
            $table->index(['college_admission_selection_rule_id', 'qualification_status'], 'cas_rule_qualification_idx');
            $table->index(['college_admission_application_id', 'qualification_status'], 'cas_application_qualification_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_scores');
    }
};
