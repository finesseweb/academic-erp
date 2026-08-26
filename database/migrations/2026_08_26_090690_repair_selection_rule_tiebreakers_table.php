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

        if (! Schema::hasTable('college_admission_selection_rule_tiebreakers')) {
            Schema::create('college_admission_selection_rule_tiebreakers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('college_admission_selection_rule_id');
                $table->unsignedSmallInteger('priority');
                $table->enum('criterion', [
                    'QUALIFYING_EXAM_SCORE',
                    'ENTRANCE_SCORE',
                    'INTERVIEW_SCORE',
                    'RELEVANT_SUBJECT_SCORE',
                    'DATE_OF_BIRTH',
                    'APPLICATION_SUBMITTED_AT',
                ]);
                $table->enum('comparison_direction', ['ASC', 'DESC']);
                $table->string('criterion_reference', 120)->nullable();
                $table->timestamps();

                $table->foreign('college_admission_selection_rule_id', 'casrt_rule_fk')
                    ->references('id')->on('college_admission_selection_rules')->cascadeOnDelete();
                $table->unique(['college_admission_selection_rule_id', 'priority'], 'casrt_rule_priority_uq');
                $table->index(['college_admission_selection_rule_id', 'criterion'], 'casrt_rule_criterion_idx');
            });
        } else {
            // Keep the repaired table compatible with Interview-aware Selection Rules.
            DB::statement("ALTER TABLE college_admission_selection_rule_tiebreakers MODIFY criterion ENUM('QUALIFYING_EXAM_SCORE','ENTRANCE_SCORE','INTERVIEW_SCORE','RELEVANT_SUBJECT_SCORE','DATE_OF_BIRTH','APPLICATION_SUBMITTED_AT') NOT NULL");
        }
    }

    public function down(): void
    {
        // Repair migration intentionally does not drop an established operational table.
    }
};
