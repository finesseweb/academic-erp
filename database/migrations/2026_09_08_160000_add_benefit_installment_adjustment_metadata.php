<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('fee_installment_schedules', function (Blueprint $table) {
            $table->decimal('paid_amount', 14, 2)->default(0)->after('amount');
            $table->decimal('allocation_percentage', 8, 4)->nullable()->after('paid_amount');
            $table->string('source_mode', 30)->nullable()->after('allocation_percentage');
        });

        Schema::table('fee_student_benefits', function (Blueprint $table) {
            $table->string('installment_adjustment_mode', 30)->nullable()->after('decision_note');
            $table->json('installment_adjustment_snapshot')->nullable()->after('installment_adjustment_mode');
        });
    }

    public function down(): void
    {
        Schema::table('fee_student_benefits', function (Blueprint $table) {
            $table->dropColumn(['installment_adjustment_mode', 'installment_adjustment_snapshot']);
        });
        Schema::table('fee_installment_schedules', function (Blueprint $table) {
            $table->dropColumn(['paid_amount', 'allocation_percentage', 'source_mode']);
        });
    }
};
