<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained('universities')->restrictOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->foreignId('program_template_id')->nullable()->constrained('program_templates')->restrictOnDelete();
            $table->foreignId('curriculum_id')->nullable()->constrained('curricula')->restrictOnDelete();

            $table->foreignId('parent_policy_id')->nullable()->constrained('academic_policies')->restrictOnDelete();
            $table->foreignId('superseded_by_id')->nullable()->constrained('academic_policies')->nullOnDelete();

            $table->string('name', 180);
            $table->string('code', 100);
            $table->string('version', 30)->default('1.0');
            $table->enum('scope_type', ['UNIVERSITY', 'PROGRAM_TEMPLATE', 'CURRICULUM'])->default('UNIVERSITY');

            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->enum('lifecycle_status', ['DRAFT', 'ACTIVE', 'RETIRED'])->default('DRAFT');
            $table->string('approval_status', 30)->default('NOT_SUBMITTED');
            $table->boolean('is_current_version')->default(false);

            $table->string('revision_type', 50)->nullable();
            $table->text('revision_reason')->nullable();
            $table->date('revision_effective_from')->nullable();

            $table->string('validation_hash', 64)->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['university_id', 'code', 'version'], 'academic_policies_code_version_unique');
            $table->index(['university_id', 'academic_session_id', 'scope_type'], 'academic_policies_scope_idx');
            $table->index(['program_template_id', 'curriculum_id'], 'academic_policies_target_idx');
            $table->index(['lifecycle_status', 'approval_status'], 'academic_policies_status_idx');
            $table->index(['parent_policy_id', 'is_current_version'], 'academic_policies_version_idx');
        });

        Schema::create('academic_policy_credit_completion_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('academic_policy_id');
            $table->foreign('academic_policy_id', 'apccr_policy_fk')
                ->references('id')->on('academic_policies')->cascadeOnDelete();

            $table->decimal('minimum_total_credits', 8, 2)->nullable();
            $table->decimal('minimum_completion_cgpa', 5, 2)->nullable();
            $table->unsignedSmallInteger('maximum_program_duration_months')->nullable();

            $table->boolean('allow_credit_transfer')->default(false);
            $table->decimal('maximum_credit_transfer_percent', 5, 2)->nullable();
            $table->boolean('allow_credit_exemption')->default(false);
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('academic_policy_id', 'academic_policy_credit_completion_unique');
        });

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

        $permissions = [
            ['code' => 'academic_policy.view', 'resource' => 'academic_policy', 'action' => 'view', 'description' => 'View Academic Policies'],
            ['code' => 'academic_policy.create', 'resource' => 'academic_policy', 'action' => 'create', 'description' => 'Create Academic Policies'],
            ['code' => 'academic_policy.update', 'resource' => 'academic_policy', 'action' => 'update', 'description' => 'Update Draft Academic Policies and rules'],
            ['code' => 'academic_policy.disable', 'resource' => 'academic_policy', 'action' => 'disable', 'description' => 'Retire or restore Academic Policies'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['code' => $permission['code']],
                [
                    ...$permission,
                    'module' => 'Academic Policies',
                    'status' => 'ACTIVE',
                    'is_sensitive' => in_array($permission['action'], ['update', 'disable'], true),
                    'is_college_delegable' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $superAdminId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');
        if ($superAdminId) {
            $permissionIds = DB::table('permissions')->whereIn('code', array_column($permissions, 'code'))->pluck('id');
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $superAdminId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_policy_credit_category_requirements');
        Schema::dropIfExists('academic_policy_credit_completion_rules');
        Schema::dropIfExists('academic_policies');

        $codes = [
            'academic_policy.view',
            'academic_policy.create',
            'academic_policy.update',
            'academic_policy.disable',
        ];
        $ids = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
    }
};
