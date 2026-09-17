<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function foreignKeyExists(string $table, string $column): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }

    public function up(): void
    {
        // Restart-safe: the original ENR-4.4 migration could fail after these
        // columns were committed by MySQL. Add each column independently.
        if (! Schema::hasColumn('student_enrollments', 'curriculum_id')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->unsignedBigInteger('curriculum_id')->nullable()->after('college_program_offering_id');
            });
        }
        if (! $this->foreignKeyExists('student_enrollments', 'curriculum_id')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->foreign('curriculum_id', 'se_curriculum_fk')->references('id')->on('curricula')->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('student_enrollments', 'specialization_id')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->unsignedBigInteger('specialization_id')->nullable()->after('discipline_id');
            });
        }
        if (! $this->foreignKeyExists('student_enrollments', 'specialization_id')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                $table->foreign('specialization_id', 'se_specialization_fk')->references('id')->on('academic_disciplines')->restrictOnDelete();
            });
        }

        if (! Schema::hasTable('student_enrollment_course_choices')) {
            Schema::create('student_enrollment_course_choices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_enrollment_id');
                $table->unsignedBigInteger('curriculum_term_id');
                $table->unsignedBigInteger('curriculum_slot_id');
                $table->unsignedBigInteger('curriculum_course_mapping_id');
                $table->unsignedBigInteger('course_id');
                $table->string('selection_source', 40);
                $table->timestamps();
            });
        }

        // Recovery path for a partially-created table: make sure all expected
        // columns exist before constraints/indexes are added.
        $columns = [
            'student_enrollment_id' => fn (Blueprint $t) => $t->unsignedBigInteger('student_enrollment_id')->nullable(),
            'curriculum_term_id' => fn (Blueprint $t) => $t->unsignedBigInteger('curriculum_term_id')->nullable(),
            'curriculum_slot_id' => fn (Blueprint $t) => $t->unsignedBigInteger('curriculum_slot_id')->nullable(),
            'curriculum_course_mapping_id' => fn (Blueprint $t) => $t->unsignedBigInteger('curriculum_course_mapping_id')->nullable(),
            'course_id' => fn (Blueprint $t) => $t->unsignedBigInteger('course_id')->nullable(),
            'selection_source' => fn (Blueprint $t) => $t->string('selection_source', 40)->nullable(),
        ];
        foreach ($columns as $column => $add) {
            if (! Schema::hasColumn('student_enrollment_course_choices', $column)) {
                Schema::table('student_enrollment_course_choices', function (Blueprint $table) use ($add) { $add($table); });
            }
        }

        $foreignKeys = [
            ['student_enrollment_id', 'sec_enrollment_fk', 'student_enrollments', 'cascade'],
            ['curriculum_term_id', 'sec_term_fk', 'curriculum_terms', 'restrict'],
            ['curriculum_slot_id', 'sec_slot_fk', 'curriculum_slots', 'restrict'],
            ['curriculum_course_mapping_id', 'sec_mapping_fk', 'curriculum_course_mappings', 'restrict'],
            ['course_id', 'sec_course_fk', 'courses', 'restrict'],
        ];
        foreach ($foreignKeys as [$column, $name, $references, $delete]) {
            if (! $this->foreignKeyExists('student_enrollment_course_choices', $column)) {
                Schema::table('student_enrollment_course_choices', function (Blueprint $table) use ($column, $name, $references, $delete) {
                    $fk = $table->foreign($column, $name)->references('id')->on($references);
                    $delete === 'cascade' ? $fk->cascadeOnDelete() : $fk->restrictOnDelete();
                });
            }
        }

        if (! $this->indexExists('student_enrollment_course_choices', 'student_enrollment_course_mapping_uq')) {
            Schema::table('student_enrollment_course_choices', function (Blueprint $table) {
                $table->unique(['student_enrollment_id', 'curriculum_course_mapping_id'], 'student_enrollment_course_mapping_uq');
            });
        }
        if (! $this->indexExists('student_enrollment_course_choices', 'student_enrollment_course_term_idx')) {
            Schema::table('student_enrollment_course_choices', function (Blueprint $table) {
                $table->index(['student_enrollment_id', 'curriculum_term_id'], 'student_enrollment_course_term_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollment_course_choices');

        if (Schema::hasColumn('student_enrollments', 'specialization_id')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                if ($this->foreignKeyExists('student_enrollments', 'specialization_id')) {
                    $table->dropForeign(['specialization_id']);
                }
                $table->dropColumn('specialization_id');
            });
        }
        if (Schema::hasColumn('student_enrollments', 'curriculum_id')) {
            Schema::table('student_enrollments', function (Blueprint $table) {
                if ($this->foreignKeyExists('student_enrollments', 'curriculum_id')) {
                    $table->dropForeign(['curriculum_id']);
                }
                $table->dropColumn('curriculum_id');
            });
        }
    }
};
