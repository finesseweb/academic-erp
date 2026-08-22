<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_course_mappings', function (Blueprint $table) {
            $table->foreignId('discipline_id')
                ->nullable()
                ->after('course_id')
                ->constrained('academic_disciplines')
                ->restrictOnDelete();

            $table->foreignId('specialization_id')
                ->nullable()
                ->after('discipline_id')
                ->constrained('academic_disciplines')
                ->restrictOnDelete();

            $table->index(
                ['curriculum_slot_id', 'discipline_id', 'status'],
                'curr_course_map_slot_disc_status_idx'
            );

            $table->index(
                ['specialization_id', 'status'],
                'curr_course_map_spec_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_course_mappings', function (Blueprint $table) {
            $table->dropIndex('curr_course_map_spec_status_idx');
            $table->dropIndex('curr_course_map_slot_disc_status_idx');
            $table->dropConstrainedForeignId('specialization_id');
            $table->dropConstrainedForeignId('discipline_id');
        });
    }
};
