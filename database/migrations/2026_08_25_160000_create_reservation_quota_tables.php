<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('university_id');
            $table->string('name', 120);
            $table->string('code', 40);
            $table->enum('nature', ['VERTICAL', 'HORIZONTAL']);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('university_id', 'rc_university_fk')->references('id')->on('universities')->restrictOnDelete();
            $table->foreign('created_by', 'rc_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'rc_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['university_id', 'code'], 'reservation_categories_university_code_uq');
            $table->index(['university_id', 'status', 'display_order'], 'reservation_categories_lookup_idx');
        });

        Schema::create('college_program_reservation_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_program_intake_id');
            $table->enum('bucket_type', ['PROGRAM', 'DISCIPLINE_GENERAL', 'SPECIALIZATION']);
            $table->string('bucket_key', 80);
            $table->unsignedBigInteger('discipline_allocation_id')->nullable();
            $table->unsignedBigInteger('specialization_allocation_id')->nullable();
            $table->unsignedInteger('basis_capacity');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('INACTIVE');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('college_program_intake_id', 'cprp_intake_fk')->references('id')->on('college_program_intakes')->restrictOnDelete();
            $table->foreign('discipline_allocation_id', 'cprp_disc_alloc_fk')->references('id')->on('college_program_intake_allocations')->restrictOnDelete();
            $table->foreign('specialization_allocation_id', 'cprp_spec_alloc_fk')->references('id')->on('college_program_intake_allocations')->restrictOnDelete();
            $table->foreign('created_by', 'cprp_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'cprp_updated_by_fk')->references('id')->on('users')->nullOnDelete();

            $table->unique(['college_program_intake_id', 'bucket_key'], 'cprp_intake_bucket_uq');
            $table->index(['college_program_intake_id', 'status'], 'cprp_intake_status_idx');
        });

        Schema::create('college_program_reservation_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_program_reservation_plan_id');
            $table->unsignedBigInteger('reservation_category_id');
            $table->unsignedInteger('seat_capacity');
            $table->unsignedSmallInteger('display_order')->default(1);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('college_program_reservation_plan_id', 'cpra_plan_fk')->references('id')->on('college_program_reservation_plans')->cascadeOnDelete();
            $table->foreign('reservation_category_id', 'cpra_category_fk')->references('id')->on('reservation_categories')->restrictOnDelete();
            $table->foreign('created_by', 'cpra_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'cpra_updated_by_fk')->references('id')->on('users')->nullOnDelete();

            $table->unique(['college_program_reservation_plan_id', 'reservation_category_id'], 'cpra_plan_category_uq');
            $table->index(['college_program_reservation_plan_id', 'display_order'], 'cpra_plan_order_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_program_reservation_allocations');
        Schema::dropIfExists('college_program_reservation_plans');
        Schema::dropIfExists('reservation_categories');
    }
};
