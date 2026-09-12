<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        /*
         * Recovery guard for development/QA installs. MySQL DDL is not fully
         * transactional, so a failed first run can leave one or more ADR 190
         * tables behind even though Laravel never records this migration as run.
         * Since this up() only executes while the migration is still pending,
         * remove those partial ADR 190 tables in dependency order and rebuild
         * them from the canonical definition below.
         */
        Schema::dropIfExists('fee_payment_refund_allocations');
        Schema::dropIfExists('fee_payment_refunds');
        Schema::dropIfExists('fee_adjustments');

        Schema::create('fee_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignId('college_id')->constrained('colleges')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('admissions')->restrictOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->foreignId('fee_demand_id')->constrained('fee_demands')->restrictOnDelete();
            $table->foreignId('fee_demand_item_id')->constrained('fee_demand_items')->restrictOnDelete();
            $table->string('adjustment_no', 50)->unique();
            $table->date('adjustment_date');
            $table->string('direction', 10); // CREDIT lowers liability; DEBIT increases liability
            $table->decimal('amount', 14, 2);
            $table->string('reason_code', 40); // CORRECTION | ROUNDING | APPROVED_RELIEF | OTHER
            $table->text('reason');
            $table->string('status', 20)->default('POSTED'); // POSTED | REVERSED
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();
            $table->index(['college_id','adjustment_date','status'], 'fee_adj_col_date_status_idx');
            $table->index(['fee_demand_id','status'], 'fee_adj_demand_status_idx');
        });

        Schema::create('fee_payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained('universities')->cascadeOnDelete();
            $table->foreignId('college_id')->constrained('colleges')->cascadeOnDelete();
            $table->foreignId('admission_id')->constrained('admissions')->restrictOnDelete();
            $table->foreignId('academic_session_id')->constrained('academic_sessions')->restrictOnDelete();
            $table->foreignId('fee_payment_id')->constrained('fee_payments')->restrictOnDelete();
            $table->string('refund_no', 50)->unique();
            $table->date('refund_date');
            $table->decimal('amount', 14, 2);
            $table->string('refund_mode', 30); // CASH | CARD | UPI | BANK_TRANSFER | CHEQUE | GATEWAY | OTHER
            $table->string('reference_no', 120)->nullable();
            $table->text('reason');
            $table->string('status', 20)->default('POSTED');
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['college_id','refund_date','status'], 'fee_ref_col_date_status_idx');
            $table->index(['fee_payment_id','status'], 'fee_ref_payment_status_idx');
        });

        Schema::create('fee_payment_refund_allocations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_payment_refund_id');
            $table->unsignedBigInteger('fee_payment_allocation_id');
            $table->unsignedBigInteger('fee_demand_id');
            $table->unsignedBigInteger('fee_demand_item_id');
            $table->unsignedBigInteger('fee_installment_schedule_id')->nullable();
            $table->unsignedBigInteger('fee_late_fine_charge_id')->nullable();

            // Explicit short FK names keep every identifier safely below MySQL's 64-character limit.
            $table->foreign('fee_payment_refund_id', 'fpra_refund_fk')
                ->references('id')->on('fee_payment_refunds')->cascadeOnDelete();
            $table->foreign('fee_payment_allocation_id', 'fpra_payment_alloc_fk')
                ->references('id')->on('fee_payment_allocations')->restrictOnDelete();
            $table->foreign('fee_demand_id', 'fpra_demand_fk')
                ->references('id')->on('fee_demands')->restrictOnDelete();
            $table->foreign('fee_demand_item_id', 'fpra_demand_item_fk')
                ->references('id')->on('fee_demand_items')->restrictOnDelete();
            $table->foreign('fee_installment_schedule_id', 'fpra_installment_fk')
                ->references('id')->on('fee_installment_schedules')->restrictOnDelete();
            $table->foreign('fee_late_fine_charge_id', 'fpra_late_fine_fk')
                ->references('id')->on('fee_late_fine_charges')->restrictOnDelete();
            $table->decimal('amount', 14, 2);
            $table->unsignedSmallInteger('sequence_no');
            $table->timestamps();
            $table->index(['fee_demand_id','fee_demand_item_id'], 'fee_ref_alloc_demand_item_idx');
        });

        $now = now();
        foreach ([
            ['college_fee_adjustment.view','view','View fee adjustments, payment reversals and refunds'],
            ['college_fee_adjustment.post','post','Post a controlled debit or credit fee adjustment'],
            ['college_fee_adjustment.reverse','reverse','Reverse a posted manual fee adjustment or posted fee receipt'],
            ['college_fee_refund.post','refund','Post a refund only against refundable paid fee allocations'],
        ] as [$code,$action,$description]) {
            DB::table('permissions')->updateOrInsert(['code'=>$code], [
                'module'=>'Fee Management','resource'=>'college_fee_adjustment','action'=>$action,
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
        foreach (['college_fee_adjustment.view','college_fee_adjustment.post','college_fee_adjustment.reverse','college_fee_refund.post'] as $code) {
            $id=DB::table('permissions')->where('code',$code)->value('id');
            if($id) DB::table('role_permissions')->where('permission_id',$id)->delete();
            DB::table('permissions')->where('code',$code)->delete();
        }
        Schema::dropIfExists('fee_payment_refund_allocations');
        Schema::dropIfExists('fee_payment_refunds');
        Schema::dropIfExists('fee_adjustments');
    }
};
