<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('college_admission_form_field_comparisons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('target_field_id')->unique();
            $table->unsignedBigInteger('source_field_id');
            $table->enum('operator', ['LT','LTE','GT','GTE','EQ','NEQ']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('target_field_id', 'caffcmp_target_fk')->references('id')->on('college_admission_form_fields')->cascadeOnDelete();
            $table->foreign('source_field_id', 'caffcmp_source_fk')->references('id')->on('college_admission_form_fields')->restrictOnDelete();
        });

        Schema::create('college_admission_form_field_copy_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('target_field_id')->unique();
            $table->unsignedBigInteger('source_field_id');
            $table->unsignedBigInteger('trigger_field_id');
            $table->json('trigger_values');
            $table->boolean('is_read_only_when_active')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('target_field_id', 'caffcpy_target_fk')->references('id')->on('college_admission_form_fields')->cascadeOnDelete();
            $table->foreign('source_field_id', 'caffcpy_source_fk')->references('id')->on('college_admission_form_fields')->restrictOnDelete();
            $table->foreign('trigger_field_id', 'caffcpy_trigger_fk')->references('id')->on('college_admission_form_fields')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_form_field_copy_rules');
        Schema::dropIfExists('college_admission_form_field_comparisons');
    }
};
