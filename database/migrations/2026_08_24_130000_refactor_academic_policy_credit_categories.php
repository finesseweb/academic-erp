<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academic_policy_credit_completion_rules')) {
            $legacyColumns = [
                'minimum_core_credits',
                'minimum_elective_credits',
                'minimum_major_credits',
                'minimum_minor_credits',
                'minimum_multidisciplinary_credits',
                'minimum_skill_credits',
                'minimum_value_added_credits',
                'minimum_internship_credits',
            ];

            $existing = array_values(array_filter(
                $legacyColumns,
                fn (string $column) => Schema::hasColumn('academic_policy_credit_completion_rules', $column)
            ));

            if ($existing !== []) {
                Schema::table('academic_policy_credit_completion_rules', function (Blueprint $table) use ($existing) {
                    $table->dropColumn($existing);
                });
            }
        }

        if (! Schema::hasTable('academic_policy_credit_category_requirements')) {
            Schema::create('academic_policy_credit_category_requirements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('academic_policy_id');
                $table->unsignedBigInteger('course_category_id');
                $table->decimal('minimum_credits', 8, 2);
                $table->decimal('maximum_credits', 8, 2)->nullable();
                $table->unsignedSmallInteger('display_order')->default(1);
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->foreign('academic_policy_id', 'apccatreq_policy_fk')
                    ->references('id')->on('academic_policies')->cascadeOnDelete();
                $table->foreign('course_category_id', 'apccatreq_category_fk')
                    ->references('id')->on('course_categories')->restrictOnDelete();
                $table->unique(['academic_policy_id', 'course_category_id'], 'apccatreq_policy_category_uq');
                $table->index(['academic_policy_id', 'display_order'], 'apccatreq_order_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_policy_credit_category_requirements');

        if (Schema::hasTable('academic_policy_credit_completion_rules')) {
            Schema::table('academic_policy_credit_completion_rules', function (Blueprint $table) {
                $table->decimal('minimum_core_credits', 8, 2)->nullable();
                $table->decimal('minimum_elective_credits', 8, 2)->nullable();
                $table->decimal('minimum_major_credits', 8, 2)->nullable();
                $table->decimal('minimum_minor_credits', 8, 2)->nullable();
                $table->decimal('minimum_multidisciplinary_credits', 8, 2)->nullable();
                $table->decimal('minimum_skill_credits', 8, 2)->nullable();
                $table->decimal('minimum_value_added_credits', 8, 2)->nullable();
                $table->decimal('minimum_internship_credits', 8, 2)->nullable();
            });
        }
    }
};
