<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_course_mappings', function (Blueprint $table) {
            $table->unsignedSmallInteger('display_order')
                ->nullable()
                ->after('course_id');

            $table->index(
                ['curriculum_slot_id', 'display_order'],
                'curriculum_course_mapping_slot_order_idx'
            );
        });

        DB::table('curriculum_course_mappings')
            ->orderBy('curriculum_slot_id')
            ->orderBy('id')
            ->get()
            ->groupBy('curriculum_slot_id')
            ->each(function ($rows) {
                foreach ($rows->values() as $index => $row) {
                    DB::table('curriculum_course_mappings')
                        ->where('id', $row->id)
                        ->update([
                            'display_order' => $index + 1,
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('curriculum_course_mappings', function (Blueprint $table) {
            $table->dropIndex('curriculum_course_mapping_slot_order_idx');
            $table->dropColumn('display_order');
        });
    }
};
