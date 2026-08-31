<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('curriculum_course_mappings', 'source_discipline_id')) {
            Schema::table('curriculum_course_mappings', function (Blueprint $table) {
                $table->unsignedBigInteger('source_discipline_id')->nullable()->after('specialization_id');
                $table->foreign('source_discipline_id', 'ccm_source_disc_fk')
                    ->references('id')->on('academic_disciplines')->nullOnDelete();
                $table->index(['source_discipline_id', 'status'], 'ccm_source_disc_status_idx');
            });
        }

        // Preserve any value entered through the earlier Course Master implementation,
        // but from this point forward the curriculum mapping is the authority.
        if (Schema::hasColumn('courses', 'source_discipline_id')) {
            DB::statement(
                'UPDATE curriculum_course_mappings ccm
                 INNER JOIN courses c ON c.id = ccm.course_id
                 SET ccm.source_discipline_id = c.source_discipline_id
                 WHERE ccm.source_discipline_id IS NULL
                   AND c.source_discipline_id IS NOT NULL'
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('curriculum_course_mappings', 'source_discipline_id')) {
            Schema::table('curriculum_course_mappings', function (Blueprint $table) {
                $table->dropForeign('ccm_source_disc_fk');
                $table->dropIndex('ccm_source_disc_status_idx');
                $table->dropColumn('source_discipline_id');
            });
        }
    }
};
