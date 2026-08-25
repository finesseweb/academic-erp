<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('college_program_intake_allocations')) {
            return;
        }

        if (
            ! Schema::hasColumn(
                'college_program_intake_allocations',
                'seat_scope_type'
            )
        ) {
            Schema::table(
                'college_program_intake_allocations',
                function (Blueprint $table) {
                    $table->enum(
                        'seat_scope_type',
                        [
                            'DISCIPLINE',
                            'ADMISSION_SPECIALIZATION',
                        ]
                    )
                        ->default('DISCIPLINE')
                        ->after('specialization_id');

                    $table->index(
                        [
                            'college_program_intake_id',
                            'seat_scope_type',
                        ],
                        'cpia_intake_scope_idx'
                    );
                }
            );
        }

        /*
         * Existing rows created before hierarchical capacity support are
         * Discipline-level allocations by definition because they have no
         * parent hierarchy yet. Keep them as DISCIPLINE.
         */
        DB::table('college_program_intake_allocations')
            ->whereNull('specialization_id')
            ->update([
                'seat_scope_type' => 'DISCIPLINE',
            ]);

        /*
         * If an older row already has a specialization_id, preserve the
         * semantic intent as a specialization child type. The newer
         * parent_allocation_id migration still requires the row to be
         * explicitly linked to its Discipline before normal use.
         */
        DB::table('college_program_intake_allocations')
            ->whereNotNull('specialization_id')
            ->update([
                'seat_scope_type' =>
                    'ADMISSION_SPECIALIZATION',
            ]);
    }

    public function down(): void
    {
        if (
            Schema::hasTable('college_program_intake_allocations') &&
            Schema::hasColumn(
                'college_program_intake_allocations',
                'seat_scope_type'
            )
        ) {
            Schema::table(
                'college_program_intake_allocations',
                function (Blueprint $table) {
                    $table->dropIndex(
                        'cpia_intake_scope_idx'
                    );
                    $table->dropColumn(
                        'seat_scope_type'
                    );
                }
            );
        }
    }
};
