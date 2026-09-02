<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_admission_interview_panels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_id');
            $table->string('name', 150);
            $table->dateTime('starts_at');
            $table->unsignedSmallInteger('slot_duration_minutes')->default(15);
            $table->string('venue', 255)->nullable();
            $table->enum('status', ['ACTIVE', 'CANCELLED'])->default('ACTIVE');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('college_id', 'caip_college_fk')->references('id')->on('colleges')->cascadeOnDelete();
            $table->foreign('created_by', 'caip_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'caip_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['college_id', 'starts_at'], 'caip_college_start_idx');
        });

        Schema::create('college_admission_interview_panel_evaluators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_interview_panel_id');
            $table->unsignedBigInteger('evaluator_user_id');
            $table->string('evaluator_name_snapshot', 255);
            $table->timestamps();
            $table->foreign('college_admission_interview_panel_id', 'caipe_panel_fk')->references('id')->on('college_admission_interview_panels')->cascadeOnDelete();
            $table->foreign('evaluator_user_id', 'caipe_user_fk')->references('id')->on('users')->restrictOnDelete();
            $table->unique(['college_admission_interview_panel_id', 'evaluator_user_id'], 'caipe_panel_user_uq');
        });

        Schema::table('college_admission_interviews', function (Blueprint $table) {
            $table->unsignedBigInteger('college_admission_interview_panel_id')->nullable()->after('college_admission_selection_rule_id');
            $table->unsignedInteger('slot_sequence')->nullable()->after('college_admission_interview_panel_id');
            $table->foreign('college_admission_interview_panel_id', 'cai_panel_fk')->references('id')->on('college_admission_interview_panels')->nullOnDelete();
            $table->index(['college_admission_interview_panel_id', 'slot_sequence'], 'cai_panel_slot_idx');
        });
    }

    public function down(): void
    {
        Schema::table('college_admission_interviews', function (Blueprint $table) {
            $table->dropForeign('cai_panel_fk');
            $table->dropIndex('cai_panel_slot_idx');
            $table->dropColumn(['college_admission_interview_panel_id', 'slot_sequence']);
        });
        Schema::dropIfExists('college_admission_interview_panel_evaluators');
        Schema::dropIfExists('college_admission_interview_panels');
    }
};
