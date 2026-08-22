<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_course_mappings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('curriculum_slot_id')
                ->constrained('curriculum_slots')
                ->restrictOnDelete();

            $table->foreignId('course_id')
                ->constrained('courses')
                ->restrictOnDelete();

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

            $table->unique(
                ['curriculum_slot_id', 'course_id'],
                'curriculum_course_mapping_slot_course_unique'
            );

            $table->index(
                ['curriculum_slot_id', 'status'],
                'curriculum_course_mapping_slot_status_idx'
            );

            $table->index(
                ['course_id', 'status'],
                'curriculum_course_mapping_course_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_course_mappings');
    }
};
