<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained('universities')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('code', 80);
            $table->string('applies_to', 80)->default('CURRICULUM');
            $table->text('description')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['university_id', 'code']);
            $table->index(['university_id', 'applies_to', 'status'], 'approval_workflow_lookup_idx');
        });

        Schema::create('approval_workflow_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_workflow_id')->constrained('approval_workflows')->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence_no');
            $table->string('name', 120);
            $table->foreignId('approver_role_id')->constrained('roles')->restrictOnDelete();
            $table->boolean('remarks_required_on_reject')->default(true);
            $table->boolean('remarks_required_on_return')->default(true);
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['approval_workflow_id', 'sequence_no'], 'approval_workflow_stage_sequence_unique');
            $table->index(['approver_role_id', 'status'], 'approval_workflow_stage_role_idx');
        });

        // Permission catalog is code/migration-controlled in this ERP.
        $permissions = [
            ['code' => 'approval_workflow.view', 'resource' => 'approval_workflow', 'action' => 'view', 'description' => 'View academic approval workflow setup'],
            ['code' => 'approval_workflow.create', 'resource' => 'approval_workflow', 'action' => 'create', 'description' => 'Create academic approval workflows'],
            ['code' => 'approval_workflow.update', 'resource' => 'approval_workflow', 'action' => 'update', 'description' => 'Update academic approval workflows and stages'],
            ['code' => 'approval_workflow.disable', 'resource' => 'approval_workflow', 'action' => 'disable', 'description' => 'Activate or deactivate academic approval workflows'],
        ];

        foreach ($permissions as $permission) {
            if (! DB::table('permissions')->where('code', $permission['code'])->exists()) {
                $row = [
                    ...$permission,
                    'module' => 'Academic Approval',
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('permissions', 'is_sensitive')) {
                    $row['is_sensitive'] = $permission['action'] === 'disable';
                }
                if (Schema::hasColumn('permissions', 'is_college_delegable')) {
                    $row['is_college_delegable'] = false;
                }

                DB::table('permissions')->insert($row);
            }
        }

        // Keep migration compatible with the existing protected SUPER_ADMIN role.
        $superAdminId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');
        if ($superAdminId) {
            $permissionIds = DB::table('permissions')
                ->whereIn('code', array_column($permissions, 'code'))
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                if (! DB::table('role_permissions')
                    ->where('role_id', $superAdminId)
                    ->where('permission_id', $permissionId)
                    ->exists()) {
                    DB::table('role_permissions')->insert([
                        'role_id' => $superAdminId,
                        'permission_id' => $permissionId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_workflow_stages');
        Schema::dropIfExists('approval_workflows');

        $codes = [
            'approval_workflow.view',
            'approval_workflow.create',
            'approval_workflow.update',
            'approval_workflow.disable',
        ];

        $ids = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('code', $codes)->delete();
    }
};
