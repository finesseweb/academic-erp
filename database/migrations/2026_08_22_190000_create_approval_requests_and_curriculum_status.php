<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('curricula', function (Blueprint $table) {
            $table->string('approval_status', 30)
                ->default('NOT_SUBMITTED')
                ->after('lifecycle_status');

            $table->index(
                ['university_id', 'approval_status'],
                'curricula_approval_status_idx'
            );
        });

        Schema::create('approval_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_workflow_id')
                ->constrained('approval_workflows')
                ->restrictOnDelete();

            $table->foreignId('university_id')
                ->constrained('universities')
                ->restrictOnDelete();

            $table->string('subject_type', 80);
            $table->unsignedBigInteger('subject_id');

            $table->enum('status', [
                'PENDING',
                'APPROVED',
                'REJECTED',
                'RETURNED',
            ])->default('PENDING');

            $table->unsignedSmallInteger('current_stage_sequence')->nullable();
            $table->foreignId('submitted_by')
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('submitted_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(
                ['subject_type', 'subject_id', 'status'],
                'approval_requests_subject_status_idx'
            );
            $table->index(
                ['university_id', 'status', 'submitted_at'],
                'approval_requests_inbox_idx'
            );
        });

        Schema::create('approval_request_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_request_id')
                ->constrained('approval_requests')
                ->cascadeOnDelete();

            $table->unsignedSmallInteger('sequence_no');
            $table->string('name', 120);
            $table->foreignId('approver_role_id')
                ->constrained('roles')
                ->restrictOnDelete();

            $table->enum('status', [
                'WAITING',
                'PENDING',
                'APPROVED',
                'REJECTED',
                'RETURNED',
            ])->default('WAITING');

            $table->foreignId('decided_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('remarks')->nullable();
            $table->timestamp('decided_at')->nullable();

            $table->boolean('remarks_required_on_reject')->default(true);
            $table->boolean('remarks_required_on_return')->default(true);

            $table->timestamps();

            $table->unique(
                ['approval_request_id', 'sequence_no'],
                'approval_request_stage_sequence_unique'
            );

            $table->index(
                ['approver_role_id', 'status'],
                'approval_request_stage_inbox_idx'
            );
        });

        $permissions = [
            [
                'code' => 'approval_request.view',
                'resource' => 'approval_request',
                'action' => 'view',
                'description' => 'View academic approval inbox and history',
            ],
            [
                'code' => 'approval_request.submit',
                'resource' => 'approval_request',
                'action' => 'submit',
                'description' => 'Submit curriculum for academic approval',
            ],
            [
                'code' => 'approval_request.decide',
                'resource' => 'approval_request',
                'action' => 'decide',
                'description' => 'Approve, reject or return academic approval requests',
            ],
        ];

        foreach ($permissions as $permission) {
            if (! DB::table('permissions')
                ->where('code', $permission['code'])
                ->exists()) {
                $row = [
                    ...$permission,
                    'module' => 'Academic Approval',
                    'status' => 'ACTIVE',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (Schema::hasColumn('permissions', 'is_sensitive')) {
                    $row['is_sensitive'] =
                        $permission['action'] === 'decide';
                }

                if (Schema::hasColumn(
                    'permissions',
                    'is_college_delegable'
                )) {
                    $row['is_college_delegable'] = false;
                }

                DB::table('permissions')->insert($row);
            }
        }

        $superAdminId = DB::table('roles')
            ->where('code', 'SUPER_ADMIN')
            ->value('id');

        if ($superAdminId) {
            $permissionIds = DB::table('permissions')
                ->whereIn(
                    'code',
                    array_column($permissions, 'code')
                )
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
        Schema::dropIfExists('approval_request_stages');
        Schema::dropIfExists('approval_requests');

        Schema::table('curricula', function (Blueprint $table) {
            $table->dropIndex('curricula_approval_status_idx');
            $table->dropColumn('approval_status');
        });

        $codes = [
            'approval_request.view',
            'approval_request.submit',
            'approval_request.decide',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('code', $codes)
            ->pluck('id');

        DB::table('role_permissions')
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        DB::table('permissions')
            ->whereIn('code', $codes)
            ->delete();
    }
};
