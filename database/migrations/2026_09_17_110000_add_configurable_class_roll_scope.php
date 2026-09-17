<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]))->isNotEmpty();
    }

    public function up(): void
    {
        // This migration is deliberately restart-safe because MySQL DDL auto-commits.
        // A failed first attempt may therefore have already added these columns.
        if (!Schema::hasColumn('student_identity_settings', 'class_roll_scope')) {
            Schema::table('student_identity_settings', function (Blueprint $table) {
                $table->string('class_roll_scope', 32)->default('PROGRAMME_OFFERING')->after('class_roll_format');
            });
        }

        if (!Schema::hasColumn('student_enrollments', 'class_roll_scope_key')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->string('class_roll_scope_key', 120)->nullable()->after('class_roll_no');
            });
        }

        DB::table('student_enrollments')
            ->whereNotNull('class_roll_no')
            ->whereNull('class_roll_scope_key')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('student_enrollments')->where('id', $row->id)->update([
                        'class_roll_scope_key' => 'OFFERING:' . $row->college_program_offering_id,
                    ]);
                }
            });

        // The legacy unique index starts with college_program_offering_id and MySQL may
        // be using it to support that column's FK. Give the FK its own stable index first.
        if (!$this->indexExists('student_enrollments', 'student_enrollment_program_offering_idx')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->index('college_program_offering_id', 'student_enrollment_program_offering_idx');
            });
        }

        if ($this->indexExists('student_enrollments', 'student_enrollment_class_roll_uq')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->dropUnique('student_enrollment_class_roll_uq');
            });
        }

        if (!$this->indexExists('student_enrollments', 'student_enrollment_class_roll_scope_uq')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->unique(
                    ['college_id', 'class_roll_scope_key', 'class_roll_no'],
                    'student_enrollment_class_roll_scope_uq'
                );
            });
        }

        if (!$this->indexExists('student_enrollments', 'student_enrollment_class_roll_scope_idx')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->index(
                    ['college_id', 'class_roll_scope_key'],
                    'student_enrollment_class_roll_scope_idx'
                );
            });
        }
    }

    public function down(): void
    {
        if ($this->indexExists('student_enrollments', 'student_enrollment_class_roll_scope_uq')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->dropUnique('student_enrollment_class_roll_scope_uq');
            });
        }

        if ($this->indexExists('student_enrollments', 'student_enrollment_class_roll_scope_idx')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->dropIndex('student_enrollment_class_roll_scope_idx');
            });
        }

        if (Schema::hasColumn('student_enrollments', 'class_roll_scope_key')) {
            DB::table('student_enrollments')->whereNotNull('class_roll_no')->update(['class_roll_scope_key' => null]);
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->dropColumn('class_roll_scope_key');
            });
        }

        if (!$this->indexExists('student_enrollments', 'student_enrollment_class_roll_uq')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->unique(['college_program_offering_id', 'class_roll_no'], 'student_enrollment_class_roll_uq');
            });
        }

        if (Schema::hasColumn('student_identity_settings', 'class_roll_scope')) {
            Schema::table('student_identity_settings', function (Blueprint $table) {
                $table->dropColumn('class_roll_scope');
            });
        }
    }
};
