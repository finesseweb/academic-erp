<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('college_program_intakes') ||
            ! Schema::hasColumn('college_program_intakes', 'allocation_mode')
        ) {
            return;
        }

        /*
         * Existing installations may still have:
         * PROGRAM_ONLY | STRUCTURED
         *
         * Map them first while the old ENUM still accepts those values.
         * Existing STRUCTURED rows are conservatively treated as DISCIPLINE,
         * which matches the previously implemented generic structured mode.
         */
        /*
         * MySQL cannot store PROGRAM/DISCIPLINE until the ENUM definition
         * accepts them. Expand temporarily, map old values, then tighten.
         */
        DB::statement("
            ALTER TABLE college_program_intakes
            MODIFY allocation_mode ENUM(
                'PROGRAM_ONLY',
                'STRUCTURED',
                'PROGRAM',
                'DISCIPLINE',
                'ADMISSION_SPECIALIZATION'
            ) NOT NULL DEFAULT 'PROGRAM'
        ");

        DB::table('college_program_intakes')
            ->where('allocation_mode', 'PROGRAM_ONLY')
            ->update(['allocation_mode' => 'PROGRAM']);

        DB::table('college_program_intakes')
            ->where('allocation_mode', 'STRUCTURED')
            ->update(['allocation_mode' => 'DISCIPLINE']);

        DB::statement("
            ALTER TABLE college_program_intakes
            MODIFY allocation_mode ENUM(
                'PROGRAM',
                'DISCIPLINE',
                'ADMISSION_SPECIALIZATION'
            ) NOT NULL DEFAULT 'PROGRAM'
        ");
    }

    public function down(): void
    {
        if (
            ! Schema::hasTable('college_program_intakes') ||
            ! Schema::hasColumn('college_program_intakes', 'allocation_mode')
        ) {
            return;
        }

        DB::statement("
            ALTER TABLE college_program_intakes
            MODIFY allocation_mode ENUM(
                'PROGRAM',
                'DISCIPLINE',
                'ADMISSION_SPECIALIZATION',
                'PROGRAM_ONLY',
                'STRUCTURED'
            ) NOT NULL DEFAULT 'PROGRAM_ONLY'
        ");

        DB::table('college_program_intakes')
            ->where('allocation_mode', 'PROGRAM')
            ->update(['allocation_mode' => 'PROGRAM_ONLY']);

        DB::table('college_program_intakes')
            ->whereIn('allocation_mode', [
                'DISCIPLINE',
                'ADMISSION_SPECIALIZATION',
            ])
            ->update(['allocation_mode' => 'STRUCTURED']);

        DB::statement("
            ALTER TABLE college_program_intakes
            MODIFY allocation_mode ENUM(
                'PROGRAM_ONLY',
                'STRUCTURED'
            ) NOT NULL DEFAULT 'PROGRAM_ONLY'
        ");
    }
};
