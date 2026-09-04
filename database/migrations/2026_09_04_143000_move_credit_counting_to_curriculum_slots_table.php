<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curriculum_slots', function (Blueprint $table) {
            $table->enum('credit_counting', ['COUNTABLE', 'NON_COUNTABLE'])
                ->default('COUNTABLE')
                ->after('credits');
        });

        // Preserve values created by the earlier mapping-level implementation.
        // A Slot becomes NON_COUNTABLE when any of its existing mappings was
        // explicitly marked NON_COUNTABLE. New behavior is owned by the Slot.
        if (Schema::hasColumn('curriculum_course_mappings', 'credit_counting')) {
            $nonCountableSlotIds = DB::table('curriculum_course_mappings')
                ->where('credit_counting', 'NON_COUNTABLE')
                ->distinct()
                ->pluck('curriculum_slot_id');

            if ($nonCountableSlotIds->isNotEmpty()) {
                DB::table('curriculum_slots')
                    ->whereIn('id', $nonCountableSlotIds)
                    ->update(['credit_counting' => 'NON_COUNTABLE']);
            }

            Schema::table('curriculum_course_mappings', function (Blueprint $table) {
                $table->dropColumn('credit_counting');
            });
        }
    }

    public function down(): void
    {
        Schema::table('curriculum_course_mappings', function (Blueprint $table) {
            $table->enum('credit_counting', ['COUNTABLE', 'NON_COUNTABLE'])
                ->default('COUNTABLE')
                ->after('specialization_id');
        });

        DB::table('curriculum_course_mappings as mapping')
            ->join('curriculum_slots as slot', 'slot.id', '=', 'mapping.curriculum_slot_id')
            ->where('slot.credit_counting', 'NON_COUNTABLE')
            ->update(['mapping.credit_counting' => 'NON_COUNTABLE']);

        Schema::table('curriculum_slots', function (Blueprint $table) {
            $table->dropColumn('credit_counting');
        });
    }
};
