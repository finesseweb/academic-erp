<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE `academic_policies` MODIFY `scope_type` ENUM('UNIVERSITY','DEGREE_LEVEL','PROGRAM_TEMPLATE','CURRICULUM') NOT NULL"
        );
    }

    public function down(): void
    {
        // A rollback cannot safely remove DEGREE_LEVEL while rows may still use it.
        // Convert any Degree-Level policies to UNIVERSITY only if the migration is
        // intentionally rolled back in a disposable/test database.
        DB::table('academic_policies')
            ->where('scope_type', 'DEGREE_LEVEL')
            ->update([
                'scope_type' => 'UNIVERSITY',
                'degree_level_id' => null,
            ]);

        DB::statement(
            "ALTER TABLE `academic_policies` MODIFY `scope_type` ENUM('UNIVERSITY','PROGRAM_TEMPLATE','CURRICULUM') NOT NULL"
        );
    }
};
