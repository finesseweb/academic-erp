<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_installment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_demand_id')->constrained('fee_demands')->cascadeOnDelete();
            $table->foreignId('fee_demand_item_id')->constrained('fee_demand_items')->cascadeOnDelete();
            $table->unsignedSmallInteger('installment_no');
            $table->decimal('amount', 14, 2);
            $table->date('due_date');
            $table->string('status', 20)->default('ACTIVE');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->timestamps();
            $table->index(['fee_demand_item_id', 'status', 'installment_no'], 'fee_inst_item_status_no_idx');
            $table->index(['fee_demand_id', 'status', 'due_date'], 'fee_inst_demand_status_due_idx');
        });

        $now = now();
        foreach ([
            ['college_fee_installment.view', 'view', 'View Fee Demand installment schedules'],
            ['college_fee_installment.manage', 'manage', 'Create or replace Fee Demand installment schedules before collection'],
        ] as [$code, $action, $description]) {
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'module' => 'Fee Management', 'resource' => 'college_fee_installment', 'action' => $action,
                'description' => $description, 'is_sensitive' => $action !== 'view', 'is_college_delegable' => true,
                'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now,
            ]);
            $permissionId = DB::table('permissions')->where('code', $code)->value('id');
            foreach (['SUPER_ADMIN', 'COLLEGE_ADMIN'] as $roleCode) {
                $roleId = DB::table('roles')->where('code', $roleCode)->where('status', 'ACTIVE')->value('id');
                if ($roleId && $permissionId) DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId], ['created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    public function down(): void
    {
        foreach (['college_fee_installment.view', 'college_fee_installment.manage'] as $code) {
            $id = DB::table('permissions')->where('code', $code)->value('id');
            if ($id) DB::table('role_permissions')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('code', $code)->delete();
        }
        Schema::dropIfExists('fee_installment_schedules');
    }
};
