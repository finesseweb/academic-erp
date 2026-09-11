<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('college_payment_gateways', 'provider_config')) {
            Schema::table('college_payment_gateways', function (Blueprint $table) {
                // Encrypted at the Eloquent layer. Holds provider-specific fields
                // so new gateway adapters do not require repeated schema changes.
                $table->text('provider_config')->nullable()->after('webhook_secret');
            });
        }

        // Credential-driven gateways (Razorpay/Cashfree/PayU) do not require a
        // Fee Head product code. NTT DATA/Atom may resolve Product ID from either
        // its credential profile default or a Fee Head override.
        DB::statement('ALTER TABLE fee_head_gateway_mappings MODIFY product_code VARCHAR(120) NULL');
    }

    public function down(): void
    {
        DB::table('fee_head_gateway_mappings')
            ->whereNull('product_code')
            ->update(['product_code' => '']);
        DB::statement('ALTER TABLE fee_head_gateway_mappings MODIFY product_code VARCHAR(120) NOT NULL');

        if (Schema::hasColumn('college_payment_gateways', 'provider_config')) {
            Schema::table('college_payment_gateways', function (Blueprint $table) {
                $table->dropColumn('provider_config');
            });
        }
    }
};
