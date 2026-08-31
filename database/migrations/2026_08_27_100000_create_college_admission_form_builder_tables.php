<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('college_admission_form_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('university_id');
            $table->unsignedBigInteger('college_id')->nullable();
            $table->unsignedBigInteger('parent_template_id')->nullable();
            $table->unsignedBigInteger('manager_user_id')->nullable();
            $table->string('name', 160);
            $table->string('code', 60);
            $table->enum('owner_scope_type', ['UNIVERSITY', 'COLLEGE']);
            $table->enum('governance_mode', ['UNIVERSITY_CONTROLLED', 'UNIVERSITY_BASE_COLLEGE_EXTENSION', 'COLLEGE_CONTROLLED'])->default('COLLEGE_CONTROLLED');
            $table->enum('admission_mode', ['REGULAR', 'DIRECT', 'BOTH'])->default('BOTH');
            $table->enum('status', ['DRAFT', 'ACTIVE', 'RETIRED'])->default('DRAFT');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('university_id', 'caft_university_fk')->references('id')->on('universities')->restrictOnDelete();
            $table->foreign('college_id', 'caft_college_fk')->references('id')->on('colleges')->restrictOnDelete();
            $table->foreign('parent_template_id', 'caft_parent_fk')->references('id')->on('college_admission_form_templates')->restrictOnDelete();
            $table->foreign('manager_user_id', 'caft_manager_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by', 'caft_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'caft_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['university_id', 'college_id', 'code'], 'caft_scope_code_uq');
            $table->index(['university_id', 'college_id', 'status'], 'caft_scope_status_idx');
        });

        Schema::create('college_admission_form_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_form_template_id');
            $table->string('title', 140);
            $table->string('code', 60);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_locked')->default(false);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
            $table->foreign('college_admission_form_template_id', 'cafs_template_fk')->references('id')->on('college_admission_form_templates')->cascadeOnDelete();
            $table->unique(['college_admission_form_template_id', 'code'], 'cafs_template_code_uq');
            $table->index(['college_admission_form_template_id', 'status', 'display_order'], 'cafs_template_order_idx');
        });

        Schema::create('college_admission_form_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_form_step_id');
            $table->string('field_key', 100);
            $table->string('label', 180);
            $table->enum('field_type', ['TEXT', 'NUMBER', 'DATE', 'EMAIL', 'PHONE', 'TEXTAREA', 'SELECT', 'RADIO', 'CHECKBOX', 'MULTISELECT', 'FILE', 'IMAGE', 'YES_NO']);
            $table->string('placeholder', 220)->nullable();
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_locked')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->json('validation_rules')->nullable();
            $table->json('visibility_rules')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
            $table->foreign('college_admission_form_step_id', 'caff_step_fk')->references('id')->on('college_admission_form_steps')->cascadeOnDelete();
            $table->unique(['college_admission_form_step_id', 'field_key'], 'caff_step_key_uq');
            $table->index(['college_admission_form_step_id', 'status', 'display_order'], 'caff_step_order_idx');
        });

        Schema::create('college_admission_form_field_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_form_field_id');
            $table->string('value', 160);
            $table->string('label', 180);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('college_admission_form_field_id', 'caffo_field_fk')->references('id')->on('college_admission_form_fields')->cascadeOnDelete();
            $table->unique(['college_admission_form_field_id', 'value'], 'caffo_field_value_uq');
        });

        Schema::create('college_admission_form_mappings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_form_template_id');
            $table->unsignedBigInteger('university_id');
            $table->unsignedBigInteger('college_id')->nullable();
            $table->unsignedBigInteger('degree_level_id')->nullable();
            $table->unsignedBigInteger('degree_id')->nullable();
            $table->unsignedBigInteger('program_template_id')->nullable();
            $table->unsignedBigInteger('college_program_offering_id')->nullable();
            $table->unsignedBigInteger('college_admission_cycle_id')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->timestamps();
            $table->foreign('college_admission_form_template_id', 'cafm_template_fk')->references('id')->on('college_admission_form_templates')->cascadeOnDelete();
            $table->foreign('university_id', 'cafm_university_fk')->references('id')->on('universities')->restrictOnDelete();
            $table->foreign('college_id', 'cafm_college_fk')->references('id')->on('colleges')->restrictOnDelete();
            $table->foreign('degree_level_id', 'cafm_degree_level_fk')->references('id')->on('degree_levels')->restrictOnDelete();
            $table->foreign('degree_id', 'cafm_degree_fk')->references('id')->on('degrees')->restrictOnDelete();
            $table->foreign('program_template_id', 'cafm_program_template_fk')->references('id')->on('program_templates')->restrictOnDelete();
            $table->foreign('college_program_offering_id', 'cafm_offering_fk')->references('id')->on('college_program_offerings')->restrictOnDelete();
            $table->foreign('college_admission_cycle_id', 'cafm_cycle_fk')->references('id')->on('college_admission_cycles')->restrictOnDelete();
            $table->index(['university_id', 'college_id', 'status'], 'cafm_scope_status_idx');
        });

        Schema::create('college_application_fee_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('university_id');
            $table->unsignedBigInteger('college_id')->nullable();
            $table->unsignedBigInteger('degree_level_id')->nullable();
            $table->unsignedBigInteger('degree_id')->nullable();
            $table->unsignedBigInteger('program_template_id')->nullable();
            $table->unsignedBigInteger('college_program_offering_id')->nullable();
            $table->unsignedBigInteger('college_admission_cycle_id')->nullable();
            $table->string('name', 160);
            $table->boolean('fee_required')->default(true);
            $table->decimal('amount', 12, 2)->default(0);
            $table->char('currency', 3)->default('INR');
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('university_id', 'cafr_university_fk')->references('id')->on('universities')->restrictOnDelete();
            $table->foreign('college_id', 'cafr_college_fk')->references('id')->on('colleges')->restrictOnDelete();
            $table->foreign('degree_level_id', 'cafr_degree_level_fk')->references('id')->on('degree_levels')->restrictOnDelete();
            $table->foreign('degree_id', 'cafr_degree_fk')->references('id')->on('degrees')->restrictOnDelete();
            $table->foreign('program_template_id', 'cafr_program_template_fk')->references('id')->on('program_templates')->restrictOnDelete();
            $table->foreign('college_program_offering_id', 'cafr_offering_fk')->references('id')->on('college_program_offerings')->restrictOnDelete();
            $table->foreign('college_admission_cycle_id', 'cafr_cycle_fk')->references('id')->on('college_admission_cycles')->restrictOnDelete();
            $table->foreign('created_by', 'cafr_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'cafr_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['university_id', 'college_id', 'status'], 'cafr_scope_status_idx');
        });

        Schema::table('college_admission_applications', function (Blueprint $table) {
            $table->unsignedBigInteger('college_admission_form_template_id')->nullable()->after('college_admission_cycle_id');
            $table->enum('admission_mode', ['REGULAR', 'DIRECT'])->default('REGULAR')->after('external_reference');
            $table->decimal('application_fee_amount', 12, 2)->default(0)->after('admission_mode');
            $table->char('application_fee_currency', 3)->default('INR')->after('application_fee_amount');
            $table->boolean('application_fee_required')->default(false)->after('application_fee_currency');
            $table->unsignedBigInteger('application_fee_rule_id')->nullable()->after('application_fee_required');
            $table->json('form_snapshot')->nullable()->after('application_fee_rule_id');
            $table->foreign('college_admission_form_template_id', 'caa_form_template_fk')->references('id')->on('college_admission_form_templates')->restrictOnDelete();
            $table->foreign('application_fee_rule_id', 'caa_fee_rule_fk')->references('id')->on('college_application_fee_rules')->restrictOnDelete();
            $table->index(['college_id', 'admission_mode', 'status'], 'caa_mode_status_idx');
        });

        Schema::create('college_admission_application_field_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_admission_application_id');
            $table->unsignedBigInteger('college_admission_form_field_id');
            $table->text('value_text')->nullable();
            $table->json('value_json')->nullable();
            $table->string('file_path', 500)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('file_mime', 120)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->timestamps();
            $table->foreign('college_admission_application_id', 'caafv_application_fk')->references('id')->on('college_admission_applications')->cascadeOnDelete();
            $table->foreign('college_admission_form_field_id', 'caafv_field_fk')->references('id')->on('college_admission_form_fields')->restrictOnDelete();
            $table->unique(['college_admission_application_id', 'college_admission_form_field_id'], 'caafv_application_field_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('college_admission_application_field_values');
        Schema::table('college_admission_applications', function (Blueprint $table) {
            $table->dropForeign('caa_form_template_fk');
            $table->dropForeign('caa_fee_rule_fk');
            $table->dropIndex('caa_mode_status_idx');
            $table->dropColumn(['college_admission_form_template_id', 'admission_mode', 'application_fee_amount', 'application_fee_currency', 'application_fee_required', 'application_fee_rule_id', 'form_snapshot']);
        });
        Schema::dropIfExists('college_application_fee_rules');
        Schema::dropIfExists('college_admission_form_mappings');
        Schema::dropIfExists('college_admission_form_field_options');
        Schema::dropIfExists('college_admission_form_fields');
        Schema::dropIfExists('college_admission_form_steps');
        Schema::dropIfExists('college_admission_form_templates');
    }
};
