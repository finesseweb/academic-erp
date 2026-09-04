<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_heads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->restrictOnDelete();
            $table->foreignId('college_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('code', 40);
            $table->string('category', 30)->default('OTHER');
            $table->text('description')->nullable();
            $table->boolean('is_refundable')->default(false);
            $table->string('status', 20)->default('INACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['university_id', 'college_id', 'status'], 'fee_heads_owner_status_idx');
        });

        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->restrictOnDelete();
            $table->foreignId('college_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('college_program_offering_id')->nullable()->constrained('college_program_offerings')->restrictOnDelete();
            $table->foreignId('program_template_id')->nullable()->constrained('program_templates')->restrictOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->string('name', 160);
            $table->string('code', 50);
            $table->string('purpose', 30)->default('ADMISSION');
            $table->char('currency', 3)->default('INR');
            $table->string('status', 20)->default('INACTIVE');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['university_id', 'college_id', 'academic_session_id', 'purpose', 'status'], 'fee_structures_scope_idx');
            $table->index(['college_program_offering_id', 'status'], 'fee_structures_offering_idx');
        });

        Schema::create('fee_structure_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_structure_id')->constrained('fee_structures')->cascadeOnDelete();
            $table->foreignId('fee_head_id')->constrained('fee_heads')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->boolean('is_mandatory')->default(true);
            $table->boolean('is_enrollment_clearance_required')->default(false);
            $table->boolean('installment_allowed')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->string('status', 20)->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['fee_structure_id', 'fee_head_id'], 'fee_structure_head_unique');
            $table->index(['fee_structure_id', 'status', 'display_order'], 'fee_structure_items_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structure_items');
        Schema::dropIfExists('fee_structures');
        Schema::dropIfExists('fee_heads');
    }
};
