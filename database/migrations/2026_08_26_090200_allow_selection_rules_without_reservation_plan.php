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

        // This migration is intentionally repair-safe because MySQL DDL is not
        // fully transactional. A previous run may have added columns/dropped
        // indexes before failing later in the migration.
        if (! Schema::hasColumn('college_admission_selection_rules', 'college_program_intake_id')) {
            Schema::table('college_admission_selection_rules', function (Blueprint $table) {
                $table->unsignedBigInteger('college_program_intake_id')->nullable()->after('id');
            });
        }

        if (! Schema::hasColumn('college_admission_selection_rules', 'bucket_type')) {
            Schema::table('college_admission_selection_rules', function (Blueprint $table) {
                $table->enum('bucket_type', ['PROGRAM', 'DISCIPLINE_GENERAL', 'SPECIALIZATION'])
                    ->nullable()
                    ->after('college_program_intake_id');
            });
        }

        if (! Schema::hasColumn('college_admission_selection_rules', 'bucket_key')) {
            Schema::table('college_admission_selection_rules', function (Blueprint $table) {
                $table->string('bucket_key', 80)->nullable()->after('bucket_type');
            });
        }

        if (! Schema::hasColumn('college_admission_selection_rules', 'basis_capacity')) {
            Schema::table('college_admission_selection_rules', function (Blueprint $table) {
                $table->unsignedInteger('basis_capacity')->nullable()->after('bucket_key');
            });
        }

        // Backfill existing reservation-backed rules before reservation becomes optional.
        DB::statement(<<<'SQL'
            UPDATE college_admission_selection_rules casr
            INNER JOIN college_program_reservation_plans cprp
                ON cprp.id = casr.college_program_reservation_plan_id
            SET casr.college_program_intake_id = COALESCE(casr.college_program_intake_id, cprp.college_program_intake_id),
                casr.bucket_type = COALESCE(casr.bucket_type, cprp.bucket_type),
                casr.bucket_key = COALESCE(casr.bucket_key, cprp.bucket_key),
                casr.basis_capacity = COALESCE(casr.basis_capacity, cprp.basis_capacity)
            WHERE casr.college_program_intake_id IS NULL
               OR casr.bucket_type IS NULL
               OR casr.bucket_key IS NULL
               OR casr.basis_capacity IS NULL
        SQL);

        // IMPORTANT: drop the FK before dropping its supporting index.
        $this->dropForeignIfExists('college_admission_selection_rules', 'casr_reservation_plan_fk');

        $this->dropIndexIfExists('college_admission_selection_rules', 'casr_plan_version_uq');
        $this->dropIndexIfExists('college_admission_selection_rules', 'casr_plan_code_version_uq');
        $this->dropIndexIfExists('college_admission_selection_rules', 'casr_plan_status_idx');

        // Existing rows are now mapped to the intake seat bucket. Reservation is optional.
        DB::statement('ALTER TABLE college_admission_selection_rules MODIFY college_program_reservation_plan_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE college_admission_selection_rules MODIFY college_program_intake_id BIGINT UNSIGNED NOT NULL');
        DB::statement("ALTER TABLE college_admission_selection_rules MODIFY bucket_type ENUM('PROGRAM','DISCIPLINE_GENERAL','SPECIALIZATION') NOT NULL");
        DB::statement('ALTER TABLE college_admission_selection_rules MODIFY bucket_key VARCHAR(80) NOT NULL');
        DB::statement('ALTER TABLE college_admission_selection_rules MODIFY basis_capacity INT UNSIGNED NOT NULL');

        if (! $this->foreignExists('college_admission_selection_rules', 'casr_intake_fk')) {
            Schema::table('college_admission_selection_rules', function (Blueprint $table) {
                $table->foreign('college_program_intake_id', 'casr_intake_fk')
                    ->references('id')->on('college_program_intakes')->restrictOnDelete();
            });
        }

        if (! $this->foreignExists('college_admission_selection_rules', 'casr_reservation_plan_fk')) {
            Schema::table('college_admission_selection_rules', function (Blueprint $table) {
                $table->foreign('college_program_reservation_plan_id', 'casr_reservation_plan_fk')
                    ->references('id')->on('college_program_reservation_plans')->restrictOnDelete();
            });
        }

        if (! $this->indexExists('college_admission_selection_rules', 'casr_bucket_version_uq')) {
            Schema::table('college_admission_selection_rules', function (Blueprint $table) {
                $table->unique(
                    ['college_program_intake_id', 'bucket_key', 'version_no'],
                    'casr_bucket_version_uq'
                );
            });
        }

        if (! $this->indexExists('college_admission_selection_rules', 'casr_bucket_code_version_uq')) {
            Schema::table('college_admission_selection_rules', function (Blueprint $table) {
                $table->unique(
                    ['college_program_intake_id', 'bucket_key', 'code', 'version_no'],
                    'casr_bucket_code_version_uq'
                );
            });
        }

        if (! $this->indexExists('college_admission_selection_rules', 'casr_bucket_status_idx')) {
            Schema::table('college_admission_selection_rules', function (Blueprint $table) {
                $table->index(
                    ['college_program_intake_id', 'bucket_key', 'status'],
                    'casr_bucket_status_idx'
                );
            });
        }

        if (! $this->indexExists('college_admission_selection_rules', 'casr_plan_status_idx')) {
            Schema::table('college_admission_selection_rules', function (Blueprint $table) {
                $table->index(
                    ['college_program_reservation_plan_id', 'status'],
                    'casr_plan_status_idx'
                );
            });
        }
    }

    public function down(): void
    {
        // Non-destructive compatibility migration. Selection rules may legitimately
        // exist without a Reservation Plan after this milestone, so rollback is
        // intentionally omitted to avoid invalidating production admission policy data.
    }

    private function foreignExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $constraint)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }

    private function dropForeignIfExists(string $table, string $constraint): void
    {
        if ($this->foreignExists($table, $constraint)) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
        }
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if ($this->indexExists($table, $index)) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$index}`");
        }
    }
};
