<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_student_benefits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('university_id');
            $table->unsignedBigInteger('college_id');
            $table->unsignedBigInteger('admission_id');
            $table->unsignedBigInteger('fee_demand_id');
            $table->unsignedBigInteger('fee_scholarship_scheme_id');
            $table->enum('application_mode', ['MANUAL_ASSIGNMENT', 'AUTOMATIC']);
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'CANCELLED'])->default('PENDING');
            $table->decimal('eligible_base_amount', 12, 2)->default(0);
            $table->decimal('calculated_benefit_amount', 12, 2)->default(0);
            $table->decimal('sanctioned_amount', 12, 2)->nullable();
            $table->json('eligibility_snapshot')->nullable();
            $table->string('scheme_name_snapshot', 150);
            $table->string('scheme_code_snapshot', 60);
            $table->enum('benefit_type_snapshot', ['SCHOLARSHIP','CONCESSION','WAIVER']);
            $table->enum('calculation_type_snapshot', ['FIXED','PERCENTAGE']);
            $table->decimal('benefit_value_snapshot', 12, 2);
            $table->decimal('maximum_benefit_amount_snapshot', 12, 2)->nullable();
            $table->text('application_note')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamp('applied_at');
            $table->unsignedBigInteger('applied_by');
            $table->timestamp('decided_at')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->foreign('university_id', 'fsb_univ_fk')->references('id')->on('universities')->restrictOnDelete();
            $table->foreign('college_id', 'fsb_college_fk')->references('id')->on('colleges')->restrictOnDelete();
            $table->foreign('admission_id', 'fsb_adm_fk')->references('id')->on('admissions')->restrictOnDelete();
            $table->foreign('fee_demand_id', 'fsb_demand_fk')->references('id')->on('fee_demands')->restrictOnDelete();
            $table->foreign('fee_scholarship_scheme_id', 'fsb_scheme_fk')->references('id')->on('fee_scholarship_schemes')->restrictOnDelete();
            $table->foreign('applied_by', 'fsb_applied_by_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('decided_by', 'fsb_decided_by_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('cancelled_by', 'fsb_cancelled_by_fk')->references('id')->on('users')->restrictOnDelete();
            $table->index(['college_id', 'status', 'fee_demand_id'], 'fsb_college_status_demand_idx');
            $table->index(['fee_demand_id', 'fee_scholarship_scheme_id', 'status'], 'fsb_demand_scheme_status_idx');
        });

        Schema::create('fee_student_benefit_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_student_benefit_id');
            $table->unsignedBigInteger('fee_demand_item_id');
            $table->unsignedBigInteger('fee_head_id');
            $table->decimal('eligible_amount', 12, 2);
            $table->decimal('calculated_amount', 12, 2);
            $table->decimal('sanctioned_amount', 12, 2)->nullable();
            $table->timestamps();

            $table->foreign('fee_student_benefit_id', 'fsbi_benefit_fk')->references('id')->on('fee_student_benefits')->cascadeOnDelete();
            $table->foreign('fee_demand_item_id', 'fsbi_demand_item_fk')->references('id')->on('fee_demand_items')->restrictOnDelete();
            $table->foreign('fee_head_id', 'fsbi_head_fk')->references('id')->on('fee_heads')->restrictOnDelete();
            $table->unique(['fee_student_benefit_id', 'fee_demand_item_id'], 'fsbi_benefit_item_uq');
            $table->index(['fee_demand_item_id', 'fee_head_id'], 'fsbi_demand_head_idx');
        });

        $now = now();
        $permissions = [
            ['college_fee_student_benefit.view','view','View student scholarship / concession / waiver assignments and sanctions',false],
            ['college_fee_student_benefit.assign','assign','Assign or apply an eligible scholarship / concession / waiver to a student demand',true],
            ['college_fee_student_benefit.approve','approve','Approve and sanction a pending student financial benefit',true],
            ['college_fee_student_benefit.reject','reject','Reject a pending student financial benefit',true],
            ['college_fee_student_benefit.cancel','cancel','Cancel a pending or rejected student financial benefit record',true],
        ];

        foreach ($permissions as [$code, $action, $description, $sensitive]) {
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'module' => 'Fee Management',
                'resource' => 'college_fee_student_benefit',
                'action' => $action,
                'description' => $description,
                'is_sensitive' => $sensitive,
                'is_college_delegable' => true,
                'status' => 'ACTIVE',
                'created_at' => $now,
                'updated_at' => $now,
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
        $codes = [
            'college_fee_student_benefit.view',
            'college_fee_student_benefit.assign',
            'college_fee_student_benefit.approve',
            'college_fee_student_benefit.reject',
            'college_fee_student_benefit.cancel',
        ];
        $ids = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('code', $codes)->delete();
        Schema::dropIfExists('fee_student_benefit_items');
        Schema::dropIfExists('fee_student_benefits');
    }
};
