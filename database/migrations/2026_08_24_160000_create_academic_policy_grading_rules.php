<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_policy_grading_rules')) {
            Schema::create('academic_policy_grading_rules', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academic_policy_id');
                $table->enum('grading_basis', ['LETTER_GRADE', 'GRADE_POINT', 'PASS_FAIL'])->default('LETTER_GRADE');
                $table->decimal('maximum_grade_point', 5, 2)->nullable();
                $table->boolean('calculate_sgpa')->default(true);
                $table->boolean('calculate_cgpa')->default(true);
                $table->unsignedTinyInteger('sgpa_decimal_places')->default(2);
                $table->unsignedTinyInteger('cgpa_decimal_places')->default(2);
                $table->enum('rounding_rule', ['NONE', 'NEAREST', 'FLOOR', 'CEIL'])->default('NEAREST');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->foreign('academic_policy_id', 'apgr_policy_fk')->references('id')->on('academic_policies')->cascadeOnDelete();
                $table->unique('academic_policy_id', 'apgr_policy_uq');
            });
        }

        if (! Schema::hasTable('academic_policy_grade_bands')) {
            Schema::create('academic_policy_grade_bands', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academic_policy_id');
                $table->decimal('minimum_percent', 5, 2);
                $table->decimal('maximum_percent', 5, 2);
                $table->string('grade_code', 30);
                $table->string('grade_label', 100)->nullable();
                $table->decimal('grade_point', 5, 2)->nullable();
                $table->boolean('is_passing')->default(true);
                $table->unsignedInteger('display_order')->default(0);
                $table->timestamps();
                $table->foreign('academic_policy_id', 'apgb_policy_fk')->references('id')->on('academic_policies')->cascadeOnDelete();
                $table->unique(['academic_policy_id', 'grade_code'], 'apgb_policy_grade_uq');
                $table->index(['academic_policy_id', 'display_order'], 'apgb_policy_order_ix');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_policy_grade_bands');
        Schema::dropIfExists('academic_policy_grading_rules');
    }
};
