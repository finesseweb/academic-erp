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

        if (! Schema::hasColumn('college_admission_selection_rules', 'interview_weight_percent')) {
            Schema::table('college_admission_selection_rules', function (Blueprint $table) {
                $table->decimal('interview_weight_percent', 5, 2)->default(0)->after('entrance_weight_percent');
            });
        }

        if (! Schema::hasColumn('college_admission_selection_rules', 'minimum_interview_score')) {
            Schema::table('college_admission_selection_rules', function (Blueprint $table) {
                $table->decimal('minimum_interview_score', 8, 3)->nullable()->after('minimum_entrance_score');
            });
        }

        // Extend the existing lifecycle-safe selection mode without changing historical rows.
        DB::statement("ALTER TABLE college_admission_selection_rules MODIFY selection_mode ENUM('MERIT','ENTRANCE','INTERVIEW','COMBINED') NOT NULL");

        if (Schema::hasTable('college_admission_selection_rule_tiebreakers')) {
            DB::statement("ALTER TABLE college_admission_selection_rule_tiebreakers MODIFY criterion ENUM('QUALIFYING_EXAM_SCORE','ENTRANCE_SCORE','INTERVIEW_SCORE','RELEVANT_SUBJECT_SCORE','DATE_OF_BIRTH','APPLICATION_SUBMITTED_AT') NOT NULL");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('college_admission_selection_rules')) {
            return;
        }

        // A rollback would lose the meaning of persisted INTERVIEW-only rules or Interview tie-breakers.
        // Refuse to narrow the enums while such records exist.
        $hasInterviewRules = DB::table('college_admission_selection_rules')
            ->where('selection_mode', 'INTERVIEW')
            ->orWhere('interview_weight_percent', '>', 0)
            ->exists();

        $hasInterviewTieBreakers = Schema::hasTable('college_admission_selection_rule_tiebreakers')
            && DB::table('college_admission_selection_rule_tiebreakers')->where('criterion', 'INTERVIEW_SCORE')->exists();

        if ($hasInterviewRules || $hasInterviewTieBreakers) {
            throw new RuntimeException('Cannot rollback Interview selection support while Interview-based Selection Rules or tie-breakers exist. Retire/remove test data first.');
        }

        if (Schema::hasTable('college_admission_selection_rule_tiebreakers')) {
            DB::statement("ALTER TABLE college_admission_selection_rule_tiebreakers MODIFY criterion ENUM('QUALIFYING_EXAM_SCORE','ENTRANCE_SCORE','RELEVANT_SUBJECT_SCORE','DATE_OF_BIRTH','APPLICATION_SUBMITTED_AT') NOT NULL");
        }

        DB::statement("ALTER TABLE college_admission_selection_rules MODIFY selection_mode ENUM('MERIT','ENTRANCE','COMBINED') NOT NULL");

        Schema::table('college_admission_selection_rules', function (Blueprint $table) {
            if (Schema::hasColumn('college_admission_selection_rules', 'minimum_interview_score')) {
                $table->dropColumn('minimum_interview_score');
            }
            if (Schema::hasColumn('college_admission_selection_rules', 'interview_weight_percent')) {
                $table->dropColumn('interview_weight_percent');
            }
        });
    }
};
