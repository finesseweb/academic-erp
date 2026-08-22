<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('curricula', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained('universities')->restrictOnDelete();
            $table->foreignId('program_template_id')->constrained('program_templates')->restrictOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 160);
            $table->string('version', 30);
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->enum('lifecycle_status', ['DRAFT', 'ACTIVE', 'RETIRED'])->default('DRAFT');
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['university_id', 'code'], 'curricula_university_code_unique');
            $table->unique(
                ['university_id', 'program_template_id', 'academic_session_id', 'version'],
                'curricula_program_session_version_unique'
            );
            $table->index(
                ['university_id', 'program_template_id', 'academic_session_id', 'lifecycle_status'],
                'curricula_scope_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('curricula');
    }
};
