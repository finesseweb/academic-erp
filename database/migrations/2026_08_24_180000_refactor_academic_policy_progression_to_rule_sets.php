<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
                $table->unique(['progression_rule_set_id', 'curriculum_term_id'], 'apprt_ruleset_term_uq');
            });
        }

        // Convert the earlier Phase 5 single generic rule into one default rule set.
        if (Schema::hasTable('academic_policy_progression_rules')) {
            foreach (DB::table('academic_policy_progression_rules')->orderBy('id')->get() as $legacy) {
                $exists = DB::table('academic_policy_progression_rule_sets')
                    ->where('academic_policy_id', $legacy->academic_policy_id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('academic_policy_progression_rule_sets')->insert([
                    'academic_policy_id' => $legacy->academic_policy_id,
                    'curriculum_id' => null,
                    'name' => 'Default Progression Rule',
                    'applies_to_all_stages' => true,
                    'evaluation_mode' => 'COMBINED',
                    'target_curriculum_term_id' => null,
                    'minimum_earned_credits' => $legacy->minimum_earned_credits,
                    'minimum_sgpa' => $legacy->minimum_sgpa,
                    'minimum_cgpa' => $legacy->minimum_cgpa,
                    'maximum_backlog_courses' => $legacy->maximum_backlog_courses,
                    'mandatory_courses_must_be_passed' => $legacy->mandatory_courses_must_be_passed,
                    'allow_carry_forward' => $legacy->allow_carry_forward,
                    'allow_detention' => $legacy->allow_detention,
                    'allow_year_back' => $legacy->allow_year_back,
                    'allow_readmission' => $legacy->allow_readmission,
                    'maximum_attempts_per_course' => $legacy->maximum_attempts_per_course,
                    'display_order' => 1,
                    'notes' => $legacy->notes,
                    'created_by' => $legacy->created_by,
                    'updated_by' => $legacy->updated_by,
                    'created_at' => $legacy->created_at,
                    'updated_at' => $legacy->updated_at,
                ]);
            }

            Schema::drop('academic_policy_progression_rules');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('academic_policy_progression_rules')) {
            Schema::create('academic_policy_progression_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academic_policy_id');
                $table->enum('evaluation_level', ['TERM', 'YEAR', 'PROGRAM_STAGE'])->default('TERM');
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
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->foreign('academic_policy_id', 'appr_policy_fk')
                    ->references('id')->on('academic_policies')->cascadeOnDelete();
                $table->unique('academic_policy_id', 'appr_policy_uq');
            });

            $firstRules = DB::table('academic_policy_progression_rule_sets')
                ->orderBy('academic_policy_id')
                ->orderBy('display_order')
                ->get()
                ->groupBy('academic_policy_id');

            foreach ($firstRules as $policyId => $rules) {
                $rule = $rules->first();

                DB::table('academic_policy_progression_rules')->insert([
                    'academic_policy_id' => $policyId,
                    'evaluation_level' => 'TERM',
                    'minimum_earned_credits' => $rule->minimum_earned_credits,
                    'minimum_sgpa' => $rule->minimum_sgpa,
                    'minimum_cgpa' => $rule->minimum_cgpa,
                    'maximum_backlog_courses' => $rule->maximum_backlog_courses,
                    'mandatory_courses_must_be_passed' => $rule->mandatory_courses_must_be_passed,
                    'allow_carry_forward' => $rule->allow_carry_forward,
                    'allow_detention' => $rule->allow_detention,
                    'allow_year_back' => $rule->allow_year_back,
                    'allow_readmission' => $rule->allow_readmission,
                    'maximum_attempts_per_course' => $rule->maximum_attempts_per_course,
                    'notes' => $rule->notes,
                    'created_by' => $rule->created_by,
                    'updated_by' => $rule->updated_by,
                    'created_at' => $rule->created_at,
                    'updated_at' => $rule->updated_at,
                ]);
            }
        }

        Schema::dropIfExists('academic_policy_progression_rule_terms');
        Schema::dropIfExists('academic_policy_progression_rule_sets');
    }
};
