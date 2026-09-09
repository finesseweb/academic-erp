<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('college_admission_seat_allocations')) {
            return;
        }

        Schema::table('college_admission_seat_allocations', function (Blueprint $table) {
            // REGULAR rows keep all of these populated. DIRECT rows intentionally
            // bypass Score / Merit / Selection Rule while preserving the same
            // Document Verification -> Seat Allocation -> Admission gates.
            $table->unsignedBigInteger('college_admission_merit_entry_id')->nullable()->change();
            $table->unsignedBigInteger('college_admission_score_id')->nullable()->change();
            $table->unsignedBigInteger('college_admission_selection_rule_id')->nullable()->change();
            $table->unsignedInteger('merit_rank')->nullable()->change();
            $table->decimal('final_weighted_score', 8, 3)->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('college_admission_seat_allocations')) {
            return;
        }

        // Rolling back after DIRECT allocations exist requires those DIRECT
        // transactional rows to be cleaned first; otherwise MySQL correctly
        // refuses to make nullable bypass fields NOT NULL.
        Schema::table('college_admission_seat_allocations', function (Blueprint $table) {
            $table->unsignedBigInteger('college_admission_merit_entry_id')->nullable(false)->change();
            $table->unsignedBigInteger('college_admission_score_id')->nullable(false)->change();
            $table->unsignedBigInteger('college_admission_selection_rule_id')->nullable(false)->change();
            $table->unsignedInteger('merit_rank')->nullable(false)->change();
            $table->decimal('final_weighted_score', 8, 3)->nullable(false)->change();
        });
    }
};
