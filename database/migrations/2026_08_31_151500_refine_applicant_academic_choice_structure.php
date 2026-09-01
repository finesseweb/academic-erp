<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_template_disciplines', function (Blueprint $table) {
            $table->boolean('specialization_required')->default(false)->after('discipline_id');
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedBigInteger('source_discipline_id')->nullable()->after('course_type_id');
            $table->foreign('source_discipline_id', 'courses_source_disc_fk')
                ->references('id')->on('academic_disciplines')->nullOnDelete();
            $table->index(['source_discipline_id', 'status'], 'courses_source_disc_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropForeign('courses_source_disc_fk');
            $table->dropIndex('courses_source_disc_status_idx');
            $table->dropColumn('source_discipline_id');
        });
        Schema::table('program_template_disciplines', function (Blueprint $table) {
            $table->dropColumn('specialization_required');
        });
    }
};
