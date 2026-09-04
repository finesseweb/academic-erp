<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_course_mappings', function (Blueprint $table) {
            $table->enum('credit_counting', ['COUNTABLE', 'NON_COUNTABLE'])
                ->default('COUNTABLE')
                ->after('source_discipline_id');
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_course_mappings', function (Blueprint $table) {
            $table->dropColumn('credit_counting');
        });
    }
};
