<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_policy_progression_rule_sets')) {
            Schema::create('academic_policy_progression_rule_sets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academic_policy_id');
                $table->unsignedBigInteger('curriculum_id')->nullable();
                $table->string('name', 150);
                $table->boolean('applies_to_all_stages')->default(false);
                $table->enum('evaluation_mode', ['COMBINED', 'EACH_TERM'])->default('COMBINED');
                $table->unsignedBigInteger('target_curriculum_term_id')->nullable();

                $table->decimal('minimum_earned_credits', 8, 2)->nullable();
                $table->decimal('minimum_sgpa', 6, 3)->nullable();
                $table->decimal('minimum_cgpa', 6, 3)->nullable();
                $table->unsignedInteger('maximum_backlog_courses')->nullable();
                $table->boolean('mandatory_courses_must_be_passed')->default(false);

                $table->boolean('allow_carry_forward')->default(true);
                $table->boolean('allow_detention')->default(true);
                $table->boolean('allow_year_back')->default(true);
                $table->boolean('allow_readmission')->default(true);
                $table->unsignedInteger('maximum_attempts_per_course')->nullable();

                $table->unsignedInteger('display_order')->default(0);
                $table->text('notes')->nullable();

                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->foreign('academic_policy_id', 'apprs_policy_fk')
                    ->references('id')->on('academic_policies')->cascadeOnDelete();
                $table->foreign('curriculum_id', 'apprs_curr_fk')
                    ->references('id')->on('curricula')->restrictOnDelete();
                $table->foreign('target_curriculum_term_id', 'apprs_target_term_fk')
                    ->references('id')->on('curriculum_terms')->restrictOnDelete();

                $table->index(['academic_policy_id', 'display_order'], 'apprs_policy_order_ix');
            });
        }

        if (! Schema::hasTable('academic_policy_progression_rule_terms')) {
            Schema::create('academic_policy_progression_rule_terms', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('progression_rule_set_id');
                $table->unsignedBigInteger('curriculum_term_id');
                $table->unsignedInteger('display_order')->default(0);
                $table->timestamps();

                $table->foreign('progression_rule_set_id', 'apprt_ruleset_fk')
                    ->references('id')->on('academic_policy_progression_rule_sets')->cascadeOnDelete();
                $table->foreign('curriculum_term_id', 'apprt_term_fk')
                    ->references('id')->on('curriculum_terms')->restrictOnDelete();

                $table->unique(
                    ['progression_rule_set_id', 'curriculum_term_id'],
                    'apprt_ruleset_term_uq'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_policy_progression_rule_terms');
        Schema::dropIfExists('academic_policy_progression_rule_sets');
    }
};
