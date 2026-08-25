<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_program_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained('colleges')->restrictOnDelete();
            $table->foreignId('program_template_id')->constrained('program_templates')->restrictOnDelete();
            $table->foreignId('curriculum_id')->constrained('curricula')->restrictOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('INACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['college_id', 'program_template_id', 'academic_session_id'],
                'college_program_offerings_unique'
            );
            $table->index(
                ['college_id', 'academic_session_id', 'status'],
                'college_program_offerings_college_session_status_idx'
            );
            $table->index(
                ['college_id', 'program_template_id', 'status'],
                'college_program_offerings_college_program_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_program_offerings');
    }
};
