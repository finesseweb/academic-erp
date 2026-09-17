<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_import_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained()->cascadeOnDelete();
            $table->foreignId('college_program_offering_id')->constrained('college_program_offerings')->cascadeOnDelete();
            $table->string('name',120);
            $table->json('mapping');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['college_id','college_program_offering_id','name'],'student_import_mapping_name_uq');
            $table->index(['college_id','college_program_offering_id'],'student_import_mapping_scope_idx');
        });
    }
    public function down(): void { Schema::dropIfExists('student_import_mappings'); }
};
