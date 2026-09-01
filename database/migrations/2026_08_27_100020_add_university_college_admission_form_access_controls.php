<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_admission_form_access_controls', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('university_id');
            $table->unsignedBigInteger('college_id');
            $table->boolean('is_enabled')->default(false);
            $table->enum('governance_mode', [
                'UNIVERSITY_CONTROLLED',
                'UNIVERSITY_BASE_COLLEGE_EXTENSION',
                'COLLEGE_CONTROLLED',
            ])->default('UNIVERSITY_BASE_COLLEGE_EXTENSION');
            $table->boolean('allow_college_fee_override')->default(false);
            $table->unsignedBigInteger('enabled_by')->nullable();
            $table->timestamp('enabled_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('university_id', 'cafac_university_fk')->references('id')->on('universities')->restrictOnDelete();
            $table->foreign('college_id', 'cafac_college_fk')->references('id')->on('colleges')->cascadeOnDelete();
            $table->foreign('enabled_by', 'cafac_enabled_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'cafac_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique('college_id', 'cafac_college_uq');
            $table->index(['university_id', 'is_enabled'], 'cafac_university_enabled_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_form_access_controls');
    }
};
