<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('college_admission_form_mappings')) return;

        // Older Stage 1 builds could save a College-created mapping of a University
        // template with college_id = null. Recover College ownership wherever the
        // linked Admission Cycle or Program Offering makes that ownership explicit.
        DB::table('college_admission_form_mappings')
            ->whereNull('college_id')
            ->whereNotNull('college_admission_cycle_id')
            ->orderBy('id')
            ->get(['id','college_admission_cycle_id'])
            ->each(function ($mapping) {
                $collegeId = DB::table('college_admission_cycles')
                    ->where('id', $mapping->college_admission_cycle_id)
                    ->value('college_id');
                if ($collegeId) DB::table('college_admission_form_mappings')->where('id',$mapping->id)->update(['college_id'=>$collegeId,'updated_at'=>now()]);
            });

        DB::table('college_admission_form_mappings')
            ->whereNull('college_id')
            ->whereNotNull('college_program_offering_id')
            ->orderBy('id')
            ->get(['id','college_program_offering_id'])
            ->each(function ($mapping) {
                $collegeId = DB::table('college_program_offerings')
                    ->where('id', $mapping->college_program_offering_id)
                    ->value('college_id');
                if ($collegeId) DB::table('college_admission_form_mappings')->where('id',$mapping->id)->update(['college_id'=>$collegeId,'updated_at'=>now()]);
            });
    }

    public function down(): void
    {
        // Ownership recovery is intentionally not reversed: nulling a correctly
        // recovered College scope would recreate the cross-College ambiguity.
    }
};
