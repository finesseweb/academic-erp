<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academic_policy_assessment_exam_rules')) return;

        Schema::create('academic_policy_assessment_exam_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_policy_id');
            $table->decimal('minimum_overall_pass_percent', 5, 2)->nullable();
            $table->boolean('require_separate_component_pass')->default(false);
            $table->enum('absence_result', ['FAIL', 'INCOMPLETE', 'AS_PER_EXAM_RULE'])->default('AS_PER_EXAM_RULE');
            $table->boolean('allow_grace_marks')->default(false);
            $table->decimal('maximum_grace_marks', 6, 2)->nullable();
            $table->boolean('allow_improvement_exam')->default(false);
            $table->boolean('allow_supplementary_exam')->default(true);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->foreign('academic_policy_id', 'apaer_policy_fk')->references('id')->on('academic_policies')->cascadeOnDelete();
            $table->unique('academic_policy_id', 'apaer_policy_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_policy_assessment_exam_rules');
    }
};
