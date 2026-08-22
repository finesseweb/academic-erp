<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curriculum_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curriculum_id')
                ->constrained('curricula')
                ->restrictOnDelete();

            // Generic sequence works for Semester I/II, Term 1/2, Year 1/2, etc.
            // The display label remains curriculum-specific through `name`.
            $table->unsignedSmallInteger('sequence_no');
            $table->string('name', 100);
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
                ['curriculum_id', 'sequence_no'],
                'curriculum_terms_curriculum_sequence_unique'
            );

            $table->index(
                ['curriculum_id', 'status', 'sequence_no'],
                'curriculum_terms_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curriculum_terms');
    }
};
