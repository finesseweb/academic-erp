<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('academic_policy_attendance_rules')) {
            return;
        }

        Schema::create('academic_policy_attendance_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_policy_id');
            $table->decimal('minimum_attendance_percent', 5, 2);
            $table->enum('calculation_level', ['COURSE', 'TERM', 'OVERALL'])->default('COURSE');
            $table->boolean('allow_condonation')->default(false);
            $table->decimal('condonation_minimum_percent', 5, 2)->nullable();
            $table->decimal('maximum_condonable_shortage_percent', 5, 2)->nullable();
            $table->boolean('attendance_required_for_exam')->default(true);
            $table->boolean('allow_special_exemption')->default(false);
            $table->enum('rounding_rule', ['NONE', 'NEAREST', 'FLOOR', 'CEIL'])->default('NONE');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->foreign('academic_policy_id', 'apatt_policy_fk')
                ->references('id')->on('academic_policies')->cascadeOnDelete();
            $table->unique('academic_policy_id', 'apatt_policy_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_policy_attendance_rules');
    }
};
