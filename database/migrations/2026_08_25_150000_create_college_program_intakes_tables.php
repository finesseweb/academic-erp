<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_program_intakes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_program_offering_id');
            $table->foreign(
                'college_program_offering_id',
                'cpi_offering_fk'
            )
                ->references('id')
                ->on('college_program_offerings')
                ->restrictOnDelete();
            $table->unsignedInteger('approved_capacity');
            $table->enum('allocation_mode', ['PROGRAM', 'DISCIPLINE', 'ADMISSION_SPECIALIZATION'])
                ->default('PROGRAM');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])
                ->default('INACTIVE');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(
                'college_program_offering_id',
                'college_program_intakes_offering_unique'
            );
            $table->index(
                ['status', 'allocation_mode'],
                'college_program_intakes_status_mode_idx'
            );
        });

        Schema::create('college_program_intake_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_program_intake_id');
            $table->foreign(
                'college_program_intake_id',
                'cpia_intake_fk'
            )
                ->references('id')
                ->on('college_program_intakes')
                ->cascadeOnDelete();
            $table->unsignedBigInteger('discipline_id');
            $table->foreign(
                'discipline_id',
                'cpia_discipline_fk'
            )
                ->references('id')
                ->on('academic_disciplines')
                ->restrictOnDelete();
            $table->unsignedBigInteger('specialization_id')->nullable();
            $table->foreign(
                'specialization_id',
                'cpia_specialization_fk'
            )
                ->references('id')
                ->on('academic_disciplines')
                ->restrictOnDelete();
            $table->enum('seat_scope_type', ['DISCIPLINE', 'ADMISSION_SPECIALIZATION']);
            $table->unsignedInteger('seat_capacity');
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])
                ->default('ACTIVE');
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(
                ['college_program_intake_id', 'display_order'],
                'college_intake_alloc_order_idx'
            );
            $table->index(
                ['college_program_intake_id', 'discipline_id'],
                'college_intake_alloc_discipline_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_program_intake_allocations');
        Schema::dropIfExists('college_program_intakes');
    }
};
