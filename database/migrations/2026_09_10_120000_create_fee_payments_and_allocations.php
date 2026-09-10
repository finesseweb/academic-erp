<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignId('college_id')->constrained('colleges')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('admissions')->restrictOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->string('receipt_no', 50)->unique();
            $table->date('payment_date');
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('INR');
            $table->string('payment_mode', 30); // CASH | CARD | UPI | BANK_TRANSFER | CHEQUE | OTHER
            $table->string('reference_no', 120)->nullable();
            $table->string('status', 20)->default('POSTED'); // POSTED | REVERSED (reversal workflow is a later ADR)
            $table->text('notes')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->index(['college_id','payment_date','status'], 'fee_pay_col_date_status_idx');
            $table->index(['admission_id','status'], 'fee_pay_adm_status_idx');
        });

        Schema::create('fee_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fee_payment_id')->constrained('fee_payments')->cascadeOnDelete();
            $table->foreignId('fee_demand_id')->constrained('fee_demands')->restrictOnDelete();
            $table->foreignId('fee_demand_item_id')->constrained('fee_demand_items')->restrictOnDelete();
            $table->foreignId('fee_installment_schedule_id')->nullable()->constrained('fee_installment_schedules')->restrictOnDelete();
            $table->foreignId('fee_late_fine_charge_id')->nullable()->constrained('fee_late_fine_charges')->restrictOnDelete();
            $table->string('source_type', 20); // DEMAND_ITEM | INSTALLMENT | LATE_FINE
            $table->date('due_date');
            $table->decimal('amount', 14, 2);
            $table->boolean('is_mandatory')->default(true);
            $table->unsignedSmallInteger('sequence_no');
            $table->timestamps();
            $table->index(['fee_demand_id','source_type'], 'fee_pay_alloc_demand_source_idx');
            $table->index(['fee_demand_item_id','source_type'], 'fee_pay_alloc_item_source_idx');
            $table->index(['fee_installment_schedule_id','source_type'], 'fee_pay_alloc_inst_source_idx');
            $table->index(['fee_late_fine_charge_id','source_type'], 'fee_pay_alloc_fine_source_idx');
        });

        $now = now();
        foreach ([
            ['college_fee_payment.view','view','View fee collection register, due groups and receipts'],
            ['college_fee_payment.collect','collect','Post offline fee collection and deterministic allocations'],
        ] as [$code,$action,$description]) {
            DB::table('permissions')->updateOrInsert(['code'=>$code], [
                'module'=>'Fee Management','resource'=>'college_fee_payment','action'=>$action,
                'description'=>$description,'is_sensitive'=>$action!=='view','is_college_delegable'=>true,
                'status'=>'ACTIVE','created_at'=>$now,'updated_at'=>$now,
            ]);
            $permissionId = DB::table('permissions')->where('code',$code)->value('id');
            foreach (['SUPER_ADMIN','COLLEGE_ADMIN'] as $roleCode) {
                $roleId = DB::table('roles')->where('code',$roleCode)->where('status','ACTIVE')->value('id');
                if ($roleId && $permissionId) DB::table('role_permissions')->updateOrInsert(
                    ['role_id'=>$roleId,'permission_id'=>$permissionId], ['created_at'=>$now,'updated_at'=>$now]
                );
            }
        }
    }

    public function down(): void
    {
        foreach (['college_fee_payment.view','college_fee_payment.collect'] as $code) {
            $id = DB::table('permissions')->where('code',$code)->value('id');
            if ($id) DB::table('role_permissions')->where('permission_id',$id)->delete();
            DB::table('permissions')->where('code',$code)->delete();
        }
        Schema::dropIfExists('fee_payment_allocations');
        Schema::dropIfExists('fee_payments');
    }
};
