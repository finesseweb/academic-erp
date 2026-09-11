<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('online_payment_transactions', function (Blueprint $table) {
            $table->foreignId('fee_demand_id')->nullable()->after('college_payment_gateway_id')->constrained('fee_demands')->restrictOnDelete();
            $table->foreignId('admission_id')->nullable()->after('fee_demand_id')->constrained('admissions')->restrictOnDelete();
            $table->foreignId('fee_payment_id')->nullable()->after('admission_id')->constrained('fee_payments')->restrictOnDelete();
            $table->timestamp('verified_at')->nullable()->after('response_context');
            $table->timestamp('posted_at')->nullable()->after('verified_at');
            $table->index(['college_id', 'fee_demand_id', 'status'], 'opt_college_demand_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('online_payment_transactions', function (Blueprint $table) {
            $table->dropIndex('opt_college_demand_status_idx');
            $table->dropForeign(['fee_payment_id']);
            $table->dropForeign(['admission_id']);
            $table->dropForeign(['fee_demand_id']);
            $table->dropColumn(['fee_payment_id', 'admission_id', 'fee_demand_id', 'verified_at', 'posted_at']);
        });
    }
};
