<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('college_admission_cycles')) {
            return;
        }

        Schema::create('college_admission_cycles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_id');
            $table->unsignedBigInteger('academic_session_id');
            $table->string('name', 180);
            $table->string('code', 80);
            $table->date('application_start_date');
            $table->date('application_end_date');
            $table->date('admission_start_date');
            $table->date('admission_end_date');
            $table->enum('status', ['INACTIVE', 'ACTIVE', 'CLOSED'])->default('INACTIVE');
            $table->text('remarks')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('college_id', 'cac_college_fk')->references('id')->on('colleges')->restrictOnDelete();
            $table->foreign('academic_session_id', 'cac_session_fk')->references('id')->on('academic_sessions')->restrictOnDelete();
            $table->foreign('created_by', 'cac_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'cac_updated_by_fk')->references('id')->on('users')->nullOnDelete();

            $table->unique(['college_id', 'code'], 'cac_college_code_uq');
            $table->index(['college_id', 'academic_session_id', 'status'], 'cac_scope_session_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_cycles');
    }
};
