<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('college_admission_cycles')) {
            return;
        }

        if (! Schema::hasColumn('college_admission_cycles', 'college_program_offering_id')) {
            Schema::table('college_admission_cycles', function (Blueprint $table) {
                $table->unsignedBigInteger('college_program_offering_id')->nullable()->after('college_id');
                $table->foreign('college_program_offering_id', 'cac_offering_fk')
                    ->references('id')->on('college_program_offerings')->restrictOnDelete();
                $table->index(['college_program_offering_id', 'status'], 'cac_offering_status_idx');
            });
        }

        // Best-effort repair for any legacy INACTIVE cycles created before Program Offering
        // became the authoritative Admission Cycle parent. Only auto-map when unambiguous.
        DB::table('college_admission_cycles')
            ->whereNull('college_program_offering_id')
            ->orderBy('id')
            ->get(['id', 'college_id', 'academic_session_id'])
            ->each(function ($cycle) {
                $offeringIds = DB::table('college_program_offerings')
                    ->where('college_id', $cycle->college_id)
                    ->where('academic_session_id', $cycle->academic_session_id)
                    ->where('status', 'ACTIVE')
                    ->pluck('id');

                if ($offeringIds->count() === 1) {
                    DB::table('college_admission_cycles')
                        ->where('id', $cycle->id)
                        ->update(['college_program_offering_id' => $offeringIds->first()]);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('college_admission_cycles') || ! Schema::hasColumn('college_admission_cycles', 'college_program_offering_id')) {
            return;
        }

        Schema::table('college_admission_cycles', function (Blueprint $table) {
            $table->dropForeign('cac_offering_fk');
            $table->dropIndex('cac_offering_status_idx');
            $table->dropColumn('college_program_offering_id');
        });
    }
};
