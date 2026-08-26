<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('college_admission_selection_rules')) {
            return;
        }

        $hasMerit = Schema::hasColumn('college_admission_selection_rules', 'minimum_merit_score');
        $hasEntrance = Schema::hasColumn('college_admission_selection_rules', 'minimum_entrance_score');
        $hasFinal = Schema::hasColumn('college_admission_selection_rules', 'minimum_final_score');

        if (! $hasMerit || ! $hasEntrance || ! $hasFinal) {
            Schema::table('college_admission_selection_rules', function (Blueprint $table) use ($hasMerit, $hasEntrance, $hasFinal) {
                if (! $hasMerit) {
                    $table->decimal('minimum_merit_score', 8, 3)->nullable()->after('entrance_weight_percent');
                }
                if (! $hasEntrance) {
                    $table->decimal('minimum_entrance_score', 8, 3)->nullable()->after('minimum_merit_score');
                }
                if (! $hasFinal) {
                    $table->decimal('minimum_final_score', 8, 3)->nullable()->after('minimum_entrance_score');
                }
            });
        }

        // Preserve earlier single-threshold data by mapping it to the relevant
        // normalized 0-100 score for the configured selection mode.
        if (Schema::hasColumn('college_admission_selection_rules', 'minimum_qualifying_score')) {
            DB::statement(<<<'SQL'
                UPDATE college_admission_selection_rules
                SET minimum_merit_score = CASE
                        WHEN selection_mode = 'MERIT' THEN COALESCE(minimum_merit_score, minimum_qualifying_score)
                        ELSE minimum_merit_score
                    END,
                    minimum_entrance_score = CASE
                        WHEN selection_mode = 'ENTRANCE' THEN COALESCE(minimum_entrance_score, minimum_qualifying_score)
                        ELSE minimum_entrance_score
                    END,
                    minimum_final_score = CASE
                        WHEN selection_mode = 'COMBINED' THEN COALESCE(minimum_final_score, minimum_qualifying_score)
                        ELSE minimum_final_score
                    END
                WHERE minimum_qualifying_score IS NOT NULL
            SQL);
        }

        if (! Schema::hasTable('college_admission_selection_rule_tiebreakers')) {
            Schema::create('college_admission_selection_rule_tiebreakers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('college_admission_selection_rule_id');
                $table->unsignedSmallInteger('priority');
                $table->enum('criterion', [
                    'QUALIFYING_EXAM_SCORE',
                    'ENTRANCE_SCORE',
                    'RELEVANT_SUBJECT_SCORE',
                    'DATE_OF_BIRTH',
                    'APPLICATION_SUBMITTED_AT',
                ]);
                $table->enum('comparison_direction', ['ASC', 'DESC']);
                $table->string('criterion_reference', 120)->nullable();
                $table->timestamps();

                $table->foreign('college_admission_selection_rule_id', 'casrt_rule_fk')
                    ->references('id')->on('college_admission_selection_rules')->cascadeOnDelete();
                $table->unique(
                    ['college_admission_selection_rule_id', 'priority'],
                    'casrt_rule_priority_uq'
                );
                $table->index(
                    ['college_admission_selection_rule_id', 'criterion'],
                    'casrt_rule_criterion_idx'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_selection_rule_tiebreakers');

        if (Schema::hasTable('college_admission_selection_rules')) {
            Schema::table('college_admission_selection_rules', function (Blueprint $table) {
                $columns = array_values(array_filter([
                    Schema::hasColumn('college_admission_selection_rules', 'minimum_merit_score') ? 'minimum_merit_score' : null,
                    Schema::hasColumn('college_admission_selection_rules', 'minimum_entrance_score') ? 'minimum_entrance_score' : null,
                    Schema::hasColumn('college_admission_selection_rules', 'minimum_final_score') ? 'minimum_final_score' : null,
                ]));

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
