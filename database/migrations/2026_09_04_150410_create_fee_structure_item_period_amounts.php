<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fee_structure_item_period_amounts')) {
            return;
        }

        Schema::create('fee_structure_item_period_amounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_structure_item_id');
            $table->unsignedTinyInteger('period_no');
            $table->decimal('amount', 12, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['fee_structure_item_id', 'period_no'], 'fsipa_item_period_uq');
            $table->foreign('fee_structure_item_id', 'fk_fsipa_item')
                ->references('id')->on('fee_structure_items')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structure_item_period_amounts');
    }
};
