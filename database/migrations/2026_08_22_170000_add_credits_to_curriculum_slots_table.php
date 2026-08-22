<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_slots', function (Blueprint $table) {
            $table->decimal('credits', 5, 2)
                ->nullable()
                ->after('course_type_id');

            $table->index(
                ['curriculum_term_id', 'status', 'credits'],
                'curriculum_slots_term_status_credits_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('curriculum_slots', function (Blueprint $table) {
            $table->dropIndex('curriculum_slots_term_status_credits_idx');
            $table->dropColumn('credits');
        });
    }
};
