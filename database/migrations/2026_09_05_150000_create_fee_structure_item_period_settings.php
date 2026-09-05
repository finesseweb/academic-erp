<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('fee_structure_item_period_settings')) {
            Schema::create('fee_structure_item_period_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('fee_structure_item_id');
                $table->unsignedTinyInteger('period_no');
                $table->boolean('is_mandatory')->default(true);
                $table->boolean('is_enrollment_clearance_required')->default(false);
                $table->boolean('installment_allowed')->default(false);
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();

                $table->unique(['fee_structure_item_id', 'period_no'], 'uq_fsips_item_period');
                $table->foreign('fee_structure_item_id', 'fk_fsips_item')->references('id')->on('fee_structure_items')->cascadeOnDelete();
                $table->foreign('created_by', 'fk_fsips_created_by')->references('id')->on('users')->nullOnDelete();
                $table->foreign('updated_by', 'fk_fsips_updated_by')->references('id')->on('users')->nullOnDelete();
            });
        }

        // Preserve the old shared flags as the initial per-period values for
        // periods that already have an explicit period amount row.
        if (Schema::hasTable('fee_structure_item_period_amounts')) {
            DB::statement("INSERT INTO fee_structure_item_period_settings
                (fee_structure_item_id, period_no, is_mandatory, is_enrollment_clearance_required, installment_allowed, display_order, status, created_by, updated_by, created_at, updated_at)
                SELECT pa.fee_structure_item_id, pa.period_no, i.is_mandatory, i.is_enrollment_clearance_required, i.installment_allowed, i.display_order, i.status, i.created_by, i.updated_by, NOW(), NOW()
                FROM fee_structure_item_period_amounts pa
                INNER JOIN fee_structure_items i ON i.id = pa.fee_structure_item_id
                LEFT JOIN fee_structure_item_period_settings ps
                    ON ps.fee_structure_item_id = pa.fee_structure_item_id AND ps.period_no = pa.period_no
                WHERE ps.id IS NULL");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structure_item_period_settings');
    }
};
