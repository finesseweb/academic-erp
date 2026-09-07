<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  Schema::create('fee_demands', function(Blueprint $t){
   $t->id(); $t->unsignedBigInteger('university_id'); $t->unsignedBigInteger('college_id'); $t->unsignedBigInteger('admission_id');
   $t->unsignedBigInteger('college_program_offering_id'); $t->unsignedBigInteger('academic_session_id'); $t->unsignedBigInteger('curriculum_id')->nullable();
   $t->unsignedInteger('billing_period_no')->default(1); $t->string('demand_no',100); $t->char('currency',3)->default('INR');
   $t->decimal('total_amount',14,2)->default(0); $t->decimal('mandatory_amount',14,2)->default(0); $t->decimal('enrollment_clearance_amount',14,2)->default(0);
   $t->decimal('paid_amount',14,2)->default(0); $t->decimal('adjusted_amount',14,2)->default(0); $t->decimal('outstanding_amount',14,2)->default(0);
   $t->enum('status',['OPEN','PARTIALLY_CLEARED','CLEARED','CANCELLED'])->default('OPEN'); $t->timestamp('generated_at'); $t->unsignedBigInteger('generated_by');
   $t->timestamp('cancelled_at')->nullable(); $t->unsignedBigInteger('cancelled_by')->nullable(); $t->text('cancellation_reason')->nullable(); $t->timestamps();
   $t->foreign('university_id','fd_uni_fk')->references('id')->on('universities')->restrictOnDelete(); $t->foreign('college_id','fd_col_fk')->references('id')->on('colleges')->restrictOnDelete();
   $t->foreign('admission_id','fd_adm_fk')->references('id')->on('admissions')->restrictOnDelete(); $t->foreign('college_program_offering_id','fd_off_fk')->references('id')->on('college_program_offerings')->restrictOnDelete();
   $t->foreign('academic_session_id','fd_sess_fk')->references('id')->on('academic_sessions')->restrictOnDelete(); $t->foreign('curriculum_id','fd_cur_fk')->references('id')->on('curricula')->restrictOnDelete();
   $t->foreign('generated_by','fd_gen_by_fk')->references('id')->on('users')->restrictOnDelete(); $t->foreign('cancelled_by','fd_can_by_fk')->references('id')->on('users')->restrictOnDelete();
   $t->unique('demand_no','fd_no_uq'); $t->index(['admission_id','billing_period_no'],'fd_adm_period_idx'); $t->index(['college_id','status'],'fd_col_status_idx');
  });
  Schema::create('fee_demand_items', function(Blueprint $t){
   $t->id(); $t->unsignedBigInteger('fee_demand_id'); $t->unsignedBigInteger('fee_structure_id'); $t->unsignedBigInteger('fee_structure_item_id'); $t->unsignedBigInteger('fee_head_id');
   $t->unsignedInteger('source_period_no')->nullable(); $t->enum('owner_type',['UNIVERSITY','COLLEGE']); $t->string('structure_name'); $t->string('structure_code'); $t->string('fee_head_name'); $t->string('fee_head_code');
   $t->string('purpose',30); $t->string('charge_basis',40); $t->decimal('amount',14,2); $t->boolean('is_mandatory'); $t->boolean('is_enrollment_clearance_required'); $t->boolean('installment_allowed'); $t->unsignedInteger('display_order')->default(0); $t->timestamps();
   $t->foreign('fee_demand_id','fdi_dem_fk')->references('id')->on('fee_demands')->cascadeOnDelete(); $t->foreign('fee_structure_id','fdi_str_fk')->references('id')->on('fee_structures')->restrictOnDelete();
   $t->foreign('fee_structure_item_id','fdi_item_fk')->references('id')->on('fee_structure_items')->restrictOnDelete(); $t->foreign('fee_head_id','fdi_head_fk')->references('id')->on('fee_heads')->restrictOnDelete();
   $t->unique(['fee_demand_id','fee_structure_item_id','source_period_no'],'fdi_source_uq'); $t->index(['fee_demand_id','is_enrollment_clearance_required'],'fdi_clear_idx');
  });
 }
 public function down(): void { Schema::dropIfExists('fee_demand_items'); Schema::dropIfExists('fee_demands'); }
};
