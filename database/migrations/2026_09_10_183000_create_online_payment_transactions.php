<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('online_payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignId('college_id')->constrained('colleges')->cascadeOnDelete();
            $table->foreignId('college_payment_gateway_id')->constrained('college_payment_gateways')->restrictOnDelete();
            $table->string('provider', 30);
            $table->string('environment', 20);
            $table->string('purpose', 50);
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('provider_order_id', 120)->nullable()->unique();
            $table->string('provider_payment_id', 120)->nullable()->unique();
            $table->string('provider_status', 50)->nullable();
            $table->string('status', 40)->default('INITIATED');
            $table->string('reference_no', 120)->unique();
            $table->json('request_context')->nullable();
            $table->json('response_context')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['college_id', 'provider', 'environment', 'status'], 'opt_college_provider_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_payment_transactions');
    }
};
