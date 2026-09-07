<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_demands', function (Blueprint $table) {
            $table->string('demand_context', 40)->nullable()->after('generation_mode');
            $table->string('billing_basis_group', 30)->nullable()->after('demand_context');
            $table->string('billing_period_label', 120)->nullable()->after('billing_basis_group');
            $table->string('bulk_run_key', 80)->nullable()->after('billing_period_label');
            $table->index(['college_program_offering_id', 'demand_context', 'billing_basis_group', 'billing_period_no'], 'fd_bulk_context_idx');
        });
    }

    public function down(): void
    {
        Schema::table('fee_demands', function (Blueprint $table) {
            $table->dropIndex('fd_bulk_context_idx');
            $table->dropColumn(['demand_context', 'billing_basis_group', 'billing_period_label', 'bulk_run_key']);
        });
    }
};
