<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_program_offering_id');
            $table->string('code', 50);
            $table->string('name', 120);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('INACTIVE');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('college_program_offering_id', 'batches_offering_fk')
                ->references('id')
                ->on('college_program_offerings')
                ->restrictOnDelete();

            $table->unique(
                ['college_program_offering_id', 'code'],
                'batches_offering_code_uq'
            );
            $table->index(
                ['college_program_offering_id', 'status'],
                'batches_offering_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
