<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_admission_interviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_application_id');
            $table->unsignedBigInteger('college_admission_application_choice_id');
            $table->unsignedBigInteger('college_admission_selection_rule_id');
            $table->string('panel_name', 150);
            $table->dateTime('scheduled_at');
            $table->string('venue', 255)->nullable();
            $table->enum('status', ['SCHEDULED','COMPLETED','CANCELLED'])->default('SCHEDULED');
            $table->decimal('final_raw_score', 10, 3)->nullable();
            $table->decimal('final_max_score', 10, 3)->nullable();
            $table->decimal('normalized_score', 8, 3)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('evaluated_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('college_admission_application_id','cai_application_fk')->references('id')->on('college_admission_applications')->cascadeOnDelete();
            $table->foreign('college_admission_application_choice_id','cai_choice_fk')->references('id')->on('college_admission_application_choices')->cascadeOnDelete();
            $table->foreign('college_admission_selection_rule_id','cai_rule_fk')->references('id')->on('college_admission_selection_rules')->restrictOnDelete();
            $table->foreign('created_by','cai_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by','cai_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique('college_admission_application_choice_id','cai_choice_uq');
            $table->index(['college_admission_selection_rule_id','status'],'cai_rule_status_idx');
        });

        Schema::create('college_admission_interview_evaluators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_interview_id');
            $table->unsignedBigInteger('evaluator_user_id');
            $table->string('evaluator_name_snapshot', 255);
            $table->decimal('raw_score', 10, 3)->nullable();
            $table->decimal('max_score', 10, 3)->nullable();
            $table->decimal('normalized_score', 8, 3)->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->foreign('college_admission_interview_id','caie_interview_fk')->references('id')->on('college_admission_interviews')->cascadeOnDelete();
            $table->foreign('evaluator_user_id','caie_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['college_admission_interview_id','evaluator_user_id'],'caie_interview_user_uq');
        });
    }
    public function down(): void { Schema::dropIfExists('college_admission_interview_evaluators'); Schema::dropIfExists('college_admission_interviews'); }
};
