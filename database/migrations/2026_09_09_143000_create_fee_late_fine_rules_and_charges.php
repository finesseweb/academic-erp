<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_late_fine_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignId('college_id')->constrained('colleges')->cascadeOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->cascadeOnDelete();
            $table->foreignId('college_program_offering_id')->constrained('college_program_offerings')->cascadeOnDelete();
            $table->foreignId('fee_head_id')->constrained('fee_heads')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('code', 60);
            $table->string('source_type', 30)->default('INSTALLMENT');
            $table->string('calculation_type', 20); // FIXED | PERCENTAGE
            $table->string('frequency', 20); // ONE_TIME | PER_DAY | PER_WEEK
            $table->decimal('value', 14, 4);
            $table->unsignedSmallInteger('grace_days')->default(0);
            $table->decimal('maximum_fine_amount', 14, 2)->nullable();
            $table->string('status', 20)->default('INACTIVE');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['college_id', 'code'], 'fee_late_rule_college_code_uq');
            $table->index(['college_id', 'status', 'college_program_offering_id'], 'fee_late_rule_college_status_off_idx');
            $table->index(['college_program_offering_id', 'fee_head_id', 'status'], 'fee_late_rule_scope_idx');
        });

        Schema::create('fee_late_fine_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_late_fine_rule_id')->constrained('fee_late_fine_rules')->restrictOnDelete();
            $table->foreignId('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignId('college_id')->constrained('colleges')->cascadeOnDelete();
            $table->foreignId('fee_demand_id')->constrained('fee_demands')->cascadeOnDelete();
            $table->foreignId('fee_demand_item_id')->constrained('fee_demand_items')->cascadeOnDelete();
            $table->foreignId('fee_installment_schedule_id')->constrained('fee_installment_schedules')->cascadeOnDelete();
            $table->date('due_date');
            $table->date('calculated_as_of');
            $table->unsignedInteger('overdue_days');
            $table->decimal('base_outstanding_amount', 14, 2);
            $table->decimal('fine_amount', 14, 2);
            $table->string('status', 20)->default('ACTIVE'); // ACTIVE | SUPERSEDED | REVERSED
            $table->unsignedBigInteger('superseded_by_id')->nullable();
            $table->foreignId('calculated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();
            $table->index(['fee_installment_schedule_id', 'status'], 'fee_late_charge_inst_status_idx');
            $table->index(['fee_demand_id', 'status'], 'fee_late_charge_demand_status_idx');
            $table->index(['college_id', 'status', 'calculated_as_of'], 'fee_late_charge_college_status_date_idx');
        });

        $now = now();
        foreach ([
            ['college_fee_late_fine.view', 'view', 'View late fine rules and posted late fine charges'],
            ['college_fee_late_fine.manage', 'manage', 'Create, update and activate late fine rules'],
            ['college_fee_late_fine.calculate', 'calculate', 'Calculate or recalculate overdue late fine charges'],
        ] as [$code, $action, $description]) {
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'module' => 'Fee Management', 'resource' => 'college_fee_late_fine', 'action' => $action,
                'description' => $description, 'is_sensitive' => $action !== 'view', 'is_college_delegable' => true,
                'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now,
            ]);
            $permissionId = DB::table('permissions')->where('code', $code)->value('id');
            foreach (['SUPER_ADMIN', 'COLLEGE_ADMIN'] as $roleCode) {
                $roleId = DB::table('roles')->where('code', $roleCode)->where('status', 'ACTIVE')->value('id');
                if ($roleId && $permissionId) {
                    DB::table('role_permissions')->updateOrInsert(
                        ['role_id' => $roleId, 'permission_id' => $permissionId],
                        ['created_at' => $now, 'updated_at' => $now]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        foreach (['college_fee_late_fine.view', 'college_fee_late_fine.manage', 'college_fee_late_fine.calculate'] as $code) {
            $id = DB::table('permissions')->where('code', $code)->value('id');
            if ($id) DB::table('role_permissions')->where('permission_id', $id)->delete();
            DB::table('permissions')->where('code', $code)->delete();
        }
        Schema::dropIfExists('fee_late_fine_charges');
        Schema::dropIfExists('fee_late_fine_rules');
    }
};
