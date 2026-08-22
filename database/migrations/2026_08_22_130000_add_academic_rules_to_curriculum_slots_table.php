<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_slots', function (Blueprint $table) {
            $table->foreignId('course_type_id')
                ->nullable()
                ->after('course_category_id')
                ->constrained('course_types')
                ->restrictOnDelete();

            $table->enum('selection_mode', ['MANDATORY', 'CHOICE'])
                ->default('MANDATORY')
                ->after('display_order');

            $table->unsignedSmallInteger('min_selection')
                ->nullable()
                ->after('selection_mode');

            $table->unsignedSmallInteger('max_selection')
                ->nullable()
                ->after('min_selection');

            $table->index(
                ['course_type_id', 'status'],
                'curriculum_slots_type_status_idx'
            );

            $table->index(
                ['selection_mode', 'status'],
                'curriculum_slots_selection_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_slots', function (Blueprint $table) {
            $table->dropIndex('curriculum_slots_selection_status_idx');
            $table->dropIndex('curriculum_slots_type_status_idx');
            $table->dropConstrainedForeignId('course_type_id');
            $table->dropColumn([
                'selection_mode',
                'min_selection',
                'max_selection',
            ]);
        });
    }
};
