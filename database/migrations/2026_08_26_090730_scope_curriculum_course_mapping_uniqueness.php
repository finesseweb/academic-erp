<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_course_mappings', function (Blueprint $table) {
            // The original Slot + Course unique key made Course Master records
            // unusable across different Discipline / Specialization contexts.
            $table->dropUnique('curriculum_course_mapping_slot_course_unique');

            $table->unique(
                ['curriculum_slot_id', 'discipline_id', 'specialization_id', 'course_id'],
                'ccm_slot_disc_spec_course_uq'
            );
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_course_mappings', function (Blueprint $table) {
            $table->dropUnique('ccm_slot_disc_spec_course_uq');

            // Rollback can only restore the old constraint when existing data
            // does not reuse the same Course across contexts. This is intentionally
            // the legacy behavior and may fail if reusable mappings now exist.
            $table->unique(
                ['curriculum_slot_id', 'course_id'],
                'curriculum_course_mapping_slot_course_unique'
            );
        });
    }
};
