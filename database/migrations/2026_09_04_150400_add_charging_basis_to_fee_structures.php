<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('fee_structures', 'charge_basis')) {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->string('charge_basis', 30)->default('ONE_TIME')->after('purpose');
            });
        }
        if (! Schema::hasColumn('fee_structures', 'charge_period_no')) {
            Schema::table('fee_structures', function (Blueprint $table) {
                $table->unsignedTinyInteger('charge_period_no')->nullable()->after('charge_basis');
            });
        }
        // Existing structures intentionally become ONE_TIME until an owner reviews them.
        // The composite index is optional for correctness and uses an explicit short name for MySQL.
        Schema::table('fee_structures', function (Blueprint $table) {
            try { $table->index(['charge_basis', 'charge_period_no'], 'fs_charge_period_idx'); } catch (\Throwable $e) { /* retry-safe after partial migration */ }
        });
    }

    public function down(): void
    {
        Schema::table('fee_structures', function (Blueprint $table) {
            try { $table->dropIndex('fs_charge_period_idx'); } catch (\Throwable $e) {}
        });
        if (Schema::hasColumn('fee_structures', 'charge_period_no')) Schema::table('fee_structures', fn (Blueprint $table) => $table->dropColumn('charge_period_no'));
        if (Schema::hasColumn('fee_structures', 'charge_basis')) Schema::table('fee_structures', fn (Blueprint $table) => $table->dropColumn('charge_basis'));
    }
};
