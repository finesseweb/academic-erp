<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_admission_selection_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_program_reservation_plan_id');
            $table->unsignedSmallInteger('version_no')->default(1);
            $table->string('name', 150);
            $table->string('code', 60);
            $table->enum('selection_mode', ['MERIT', 'ENTRANCE', 'COMBINED']);
            $table->decimal('merit_weight_percent', 5, 2)->default(0);
            $table->decimal('entrance_weight_percent', 5, 2)->default(0);
            $table->decimal('minimum_qualifying_score', 8, 3)->nullable();
            $table->string('roster_rule_reference', 255)->nullable();
            $table->text('tie_breaker_rules')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['INACTIVE', 'ACTIVE', 'RETIRED'])->default('INACTIVE');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('college_program_reservation_plan_id', 'casr_reservation_plan_fk')
                ->references('id')->on('college_program_reservation_plans')->restrictOnDelete();
            $table->foreign('created_by', 'casr_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'casr_updated_by_fk')->references('id')->on('users')->nullOnDelete();

            $table->unique(['college_program_reservation_plan_id', 'version_no'], 'casr_plan_version_uq');
            $table->unique(['college_program_reservation_plan_id', 'code', 'version_no'], 'casr_plan_code_version_uq');
            $table->index(['college_program_reservation_plan_id', 'status'], 'casr_plan_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_selection_rules');
    }
};
