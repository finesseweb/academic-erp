<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This migration may be re-run after a MySQL DDL failure. MySQL can leave
        // earlier ALTER/CREATE statements committed even though Laravel did not
        // record the migration, so guard the added column and rebuild the new
        // adoption table before creating it again.
        if (! Schema::hasColumn('fee_structures', 'college_applicability')) {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->string('college_applicability', 20)->nullable()->after('purpose');
            });
        }

        DB::table('fee_structures')
            ->whereNull('college_id')
            ->whereNull('college_applicability')
            ->update(['college_applicability' => 'OPTIONAL']);

        // A failed first run can leave this brand-new table behind because MySQL
        // DDL is not fully transactional. It cannot contain valid feature data
        // before this migration succeeds, so recreate it cleanly.
        Schema::dropIfExists('college_fee_structure_adoptions');

        Schema::create('college_fee_structure_adoptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('university_fee_structure_id');
            $table->unsignedBigInteger('college_id');
            $table->string('status', 20)->default('ADOPTED');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Explicit short names are required because MySQL identifiers are
            // limited to 64 characters and Laravel's generated FK name for
            // university_fee_structure_id exceeds that limit.
            $table->foreign('university_fee_structure_id', 'fk_cfsa_structure')
                ->references('id')->on('fee_structures')->restrictOnDelete();
            $table->foreign('college_id', 'fk_cfsa_college')
                ->references('id')->on('colleges')->restrictOnDelete();
            $table->foreign('created_by', 'fk_cfsa_created_by')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'fk_cfsa_updated_by')
                ->references('id')->on('users')->nullOnDelete();

            $table->unique(
                ['university_fee_structure_id', 'college_id'],
                'uq_cfsa_structure_college'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_fee_structure_adoptions');

        if (Schema::hasColumn('fee_structures', 'college_applicability')) {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->dropColumn('college_applicability');
            });
        }
    }
};
