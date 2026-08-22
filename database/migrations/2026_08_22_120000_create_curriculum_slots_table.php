<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_slots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('curriculum_term_id')
                ->constrained('curriculum_terms')
                ->restrictOnDelete();

            $table->foreignId('course_category_id')
                ->constrained('course_categories')
                ->restrictOnDelete();

            $table->string('name', 120);
            $table->unsignedSmallInteger('display_order');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

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
                ['curriculum_term_id', 'display_order'],
                'curriculum_slots_term_order_unique'
            );

            $table->index(
                ['curriculum_term_id', 'status', 'display_order'],
                'curriculum_slots_lookup_idx'
            );

            $table->index(
                ['course_category_id', 'status'],
                'curriculum_slots_category_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_slots');
    }
};
