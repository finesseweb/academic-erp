<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('fee_structures', 'curriculum_id')) {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->unsignedBigInteger('curriculum_id')->nullable()->after('program_template_id');
                $table->foreign('curriculum_id', 'fk_fee_structure_curriculum')->references('id')->on('curricula')->restrictOnDelete();
                $table->index(['curriculum_id', 'status'], 'idx_fee_structure_curr_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('fee_structures', 'curriculum_id')) {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->dropForeign('fk_fee_structure_curriculum');
                $table->dropIndex('idx_fee_structure_curr_status');
                $table->dropColumn('curriculum_id');
            });
        }
    }
};
