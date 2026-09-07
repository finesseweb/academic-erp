<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('fee_structure_item_period_exclusions')) {
            return;
        }

        Schema::create('fee_structure_item_period_exclusions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_structure_item_id');
            $table->unsignedTinyInteger('period_no');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['fee_structure_item_id', 'period_no'], 'fsipe_item_period_uq');
            $table->foreign('fee_structure_item_id', 'fk_fsipe_item')
                ->references('id')->on('fee_structure_items')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structure_item_period_exclusions');
    }
};
