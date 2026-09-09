<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_structure_items', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('amount');
        });

        Schema::table('fee_structure_item_period_settings', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('period_no');
        });

        Schema::table('fee_demand_items', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('source_period_no');
            $table->index(['fee_demand_id', 'due_date'], 'fdi_demand_due_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fee_demand_items', function (Blueprint $table) {
            $table->dropIndex('fdi_demand_due_idx');
            $table->dropColumn('due_date');
        });
        Schema::table('fee_structure_item_period_settings', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
        Schema::table('fee_structure_items', function (Blueprint $table) {
            $table->dropColumn('due_date');
        });
    }
};
