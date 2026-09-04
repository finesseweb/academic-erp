<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('batch_id');
            $table->string('code', 50);
            $table->string('name', 120);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('INACTIVE');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('batch_id', 'sections_batch_fk')
                ->references('id')->on('batches')->restrictOnDelete();
            $table->unique(['batch_id', 'code'], 'sections_batch_code_uq');
            $table->index(['batch_id', 'status'], 'sections_batch_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
