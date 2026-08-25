<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('college_program_intakes')) {
            return;
        }

        /*
         * Intake allocation mode is now only:
         * PROGRAM    = one Program-level capacity, no child allocations.
         * DISCIPLINE = Discipline capacities with optional child
         *              Specialization capacities.
         *
         * Older ADMISSION_SPECIALIZATION rows are mapped into DISCIPLINE,
         * because Specialization is now a child of its Discipline capacity.
         */
        DB::statement("
            ALTER TABLE college_program_intakes
            MODIFY allocation_mode ENUM(
                'PROGRAM',
                'DISCIPLINE',
                'ADMISSION_SPECIALIZATION'
            ) NOT NULL DEFAULT 'PROGRAM'
        ");

        DB::table('college_program_intakes')
            ->where('allocation_mode', 'ADMISSION_SPECIALIZATION')
            ->update(['allocation_mode' => 'DISCIPLINE']);

        DB::statement("
            ALTER TABLE college_program_intakes
            MODIFY allocation_mode ENUM(
                'PROGRAM',
                'DISCIPLINE'
            ) NOT NULL DEFAULT 'PROGRAM'
        ");

        if (
            Schema::hasTable('college_program_intake_allocations') &&
            ! Schema::hasColumn(
                'college_program_intake_allocations',
                'parent_allocation_id'
            )
        ) {
            Schema::table(
                'college_program_intake_allocations',
                function (Blueprint $table) {
                    $table->unsignedBigInteger('parent_allocation_id')
                        ->nullable()
                        ->after('college_program_intake_id');

                    $table->foreign(
                        'parent_allocation_id',
                        'cpia_parent_fk'
                    )
                        ->references('id')
                        ->on('college_program_intake_allocations')
                        ->cascadeOnDelete();

                    $table->index(
                        ['college_program_intake_id', 'parent_allocation_id'],
                        'cpia_intake_parent_idx'
                    );
                }
            );
        }

        /*
         * Old seat_scope_type ADMISSION_SPECIALIZATION remains valid as a
         * row type, but it now means a specialization child allocation and
         * MUST have parent_allocation_id pointing to its Discipline row.
         *
         * We do not auto-link old specialization rows because guessing the
         * intended parent would be unsafe. If any such rows exist, they are
         * left for explicit correction while the Intake remains INACTIVE.
         */
    }

    public function down(): void
    {
        if (
            Schema::hasTable('college_program_intake_allocations') &&
            Schema::hasColumn(
                'college_program_intake_allocations',
                'parent_allocation_id'
            )
        ) {
            Schema::table(
                'college_program_intake_allocations',
                function (Blueprint $table) {
                    $table->dropForeign('cpia_parent_fk');
                    $table->dropIndex('cpia_intake_parent_idx');
                    $table->dropColumn('parent_allocation_id');
                }
            );
        }

        if (
            Schema::hasTable('college_program_intakes') &&
            Schema::hasColumn('college_program_intakes', 'allocation_mode')
        ) {
            DB::statement("
                ALTER TABLE college_program_intakes
                MODIFY allocation_mode ENUM(
                    'PROGRAM',
                    'DISCIPLINE',
                    'ADMISSION_SPECIALIZATION'
                ) NOT NULL DEFAULT 'PROGRAM'
            ");
        }
    }
};
