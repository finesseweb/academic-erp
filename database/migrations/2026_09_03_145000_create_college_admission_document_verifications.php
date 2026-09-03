<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('college_admission_document_verifications')) {
            Schema::create('college_admission_document_verifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('college_id');
                $table->unsignedBigInteger('college_admission_application_id');
                $table->enum('status', ['PENDING', 'VERIFIED', 'DEFICIENT'])->default('PENDING');
                $table->text('notes')->nullable();
                $table->timestamp('finalized_at')->nullable();
                $table->unsignedBigInteger('finalized_by')->nullable();
                $table->timestamps();

                $table->foreign('college_id', 'cadv_college_fk')->references('id')->on('colleges')->restrictOnDelete();
                $table->foreign('college_admission_application_id', 'cadv_application_fk')->references('id')->on('college_admission_applications')->restrictOnDelete();
                $table->foreign('finalized_by', 'cadv_finalized_by_fk')->references('id')->on('users')->restrictOnDelete();
                $table->unique('college_admission_application_id', 'cadv_application_uq');
                $table->index(['college_id', 'status'], 'cadv_college_status_idx');
            });
        }

        if (! Schema::hasTable('college_admission_document_verification_items')) {
            Schema::create('college_admission_document_verification_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('college_admission_document_verification_id');
                $table->unsignedBigInteger('college_admission_application_field_value_id');
                $table->unsignedBigInteger('college_admission_form_field_id');
                $table->string('field_label', 255);
                $table->string('file_name', 255)->nullable();
                $table->enum('status', ['PENDING', 'VERIFIED', 'REJECTED', 'WAIVED'])->default('PENDING');
                $table->text('remarks')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamps();

                $table->foreign('college_admission_document_verification_id', 'cadvi_verification_fk')->references('id')->on('college_admission_document_verifications')->cascadeOnDelete();
                $table->foreign('college_admission_application_field_value_id', 'cadvi_value_fk')->references('id')->on('college_admission_application_field_values')->restrictOnDelete();
                $table->foreign('college_admission_form_field_id', 'cadvi_field_fk')->references('id')->on('college_admission_form_fields')->restrictOnDelete();
                $table->foreign('reviewed_by', 'cadvi_reviewed_by_fk')->references('id')->on('users')->restrictOnDelete();
                $table->unique('college_admission_application_field_value_id', 'cadvi_value_uq');
                $table->index(['college_admission_document_verification_id', 'status'], 'cadvi_verification_status_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_document_verification_items');
        Schema::dropIfExists('college_admission_document_verifications');
    }
};
