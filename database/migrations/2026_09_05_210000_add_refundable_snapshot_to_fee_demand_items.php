<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fee_demand_items', function (Blueprint $table) {
            $table->boolean('is_refundable')->default(false)->after('installment_allowed');
        });

        // Existing test/legacy demand rows predate the snapshot column. Seed their
        // snapshot once from the Fee Head value that exists at migration time.
        DB::statement('UPDATE fee_demand_items fdi INNER JOIN fee_heads fh ON fh.id = fdi.fee_head_id SET fdi.is_refundable = fh.is_refundable');
    }

    public function down(): void
    {
        Schema::table('fee_demand_items', function (Blueprint $table) {
            $table->dropColumn('is_refundable');
        });
    }
};
