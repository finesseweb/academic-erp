<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fee_scholarship_schemes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('university_id');
            $table->unsignedBigInteger('college_id')->nullable();
            $table->unsignedBigInteger('academic_session_id');
            $table->unsignedBigInteger('program_template_id')->nullable();
            $table->unsignedBigInteger('college_program_offering_id')->nullable();
            $table->string('name', 150);
            $table->string('code', 60);
            $table->enum('benefit_type', ['SCHOLARSHIP','CONCESSION','WAIVER'])->default('SCHOLARSHIP');
            $table->enum('calculation_type', ['FIXED','PERCENTAGE']);
            $table->decimal('benefit_value', 12, 2);
            $table->decimal('maximum_benefit_amount', 12, 2)->nullable();
            $table->enum('eligibility_mode', ['OPEN','RESERVATION_CATEGORY'])->default('OPEN');
            $table->enum('approval_mode', ['AUTOMATIC','MANUAL'])->default('MANUAL');
            $table->text('description')->nullable();
            $table->enum('status', ['INACTIVE','ACTIVE'])->default('INACTIVE');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('university_id', 'fss_university_fk')->references('id')->on('universities')->restrictOnDelete();
            $table->foreign('college_id', 'fss_college_fk')->references('id')->on('colleges')->restrictOnDelete();
            $table->foreign('academic_session_id', 'fss_session_fk')->references('id')->on('academic_sessions')->restrictOnDelete();
            $table->foreign('program_template_id', 'fss_program_fk')->references('id')->on('program_templates')->restrictOnDelete();
            $table->foreign('college_program_offering_id', 'fss_offering_fk')->references('id')->on('college_program_offerings')->restrictOnDelete();
            $table->unique(['university_id','college_id','academic_session_id','code'], 'fss_owner_session_code_uq');
            $table->index(['university_id','college_id','status'], 'fss_owner_status_idx');
        });

        Schema::create('fee_scholarship_scheme_heads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_scholarship_scheme_id');
            $table->unsignedBigInteger('fee_head_id');
            $table->timestamps();
            $table->foreign('fee_scholarship_scheme_id', 'fssh_scheme_fk')->references('id')->on('fee_scholarship_schemes')->cascadeOnDelete();
            $table->foreign('fee_head_id', 'fssh_head_fk')->references('id')->on('fee_heads')->restrictOnDelete();
            $table->unique(['fee_scholarship_scheme_id','fee_head_id'], 'fssh_scheme_head_uq');
        });

        Schema::create('fee_scholarship_scheme_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fee_scholarship_scheme_id');
            $table->unsignedBigInteger('reservation_category_id');
            $table->timestamps();
            $table->foreign('fee_scholarship_scheme_id', 'fssc_scheme_fk')->references('id')->on('fee_scholarship_schemes')->cascadeOnDelete();
            $table->foreign('reservation_category_id', 'fssc_category_fk')->references('id')->on('reservation_categories')->restrictOnDelete();
            $table->unique(['fee_scholarship_scheme_id','reservation_category_id'], 'fssc_scheme_category_uq');
        });

        $now = now();
        $permissions = [
            ['fee_scholarship.view','view','View University scholarship/concession/waiver schemes',false,false],
            ['fee_scholarship.create','create','Create University scholarship/concession/waiver schemes',true,false],
            ['fee_scholarship.update','update','Update University scholarship/concession/waiver schemes',true,false],
            ['fee_scholarship.enable','enable','Activate University scholarship/concession/waiver schemes',true,false],
            ['fee_scholarship.disable','disable','Deactivate University scholarship/concession/waiver schemes',true,false],
            ['college_fee_scholarship.view','view','View College and inherited University scholarship schemes',false,true],
            ['college_fee_scholarship.create','create','Create College scholarship/concession/waiver schemes',true,true],
            ['college_fee_scholarship.update','update','Update College scholarship/concession/waiver schemes',true,true],
            ['college_fee_scholarship.enable','enable','Activate College scholarship/concession/waiver schemes',true,true],
            ['college_fee_scholarship.disable','disable','Deactivate College scholarship/concession/waiver schemes',true,true],
        ];
        foreach ($permissions as [$code,$action,$description,$sensitive,$delegable]) {
            DB::table('permissions')->updateOrInsert(['code'=>$code], [
                'module'=>'Fee Management','resource'=>str_starts_with($code,'college_')?'college_fee_scholarship':'fee_scholarship',
                'action'=>$action,'description'=>$description,'is_sensitive'=>$sensitive,'is_college_delegable'=>$delegable,
                'status'=>'ACTIVE','created_at'=>$now,'updated_at'=>$now,
            ]);
            $permissionId = DB::table('permissions')->where('code',$code)->value('id');
            $roles = str_starts_with($code,'college_') ? ['SUPER_ADMIN','COLLEGE_ADMIN'] : ['SUPER_ADMIN'];
            foreach ($roles as $roleCode) {
                $roleId = DB::table('roles')->where('code',$roleCode)->where('status','ACTIVE')->value('id');
                if ($roleId && $permissionId) DB::table('role_permissions')->updateOrInsert(['role_id'=>$roleId,'permission_id'=>$permissionId],['created_at'=>$now,'updated_at'=>$now]);
            }
        }
    }

    public function down(): void
    {
        $codes = ['fee_scholarship.view','fee_scholarship.create','fee_scholarship.update','fee_scholarship.enable','fee_scholarship.disable','college_fee_scholarship.view','college_fee_scholarship.create','college_fee_scholarship.update','college_fee_scholarship.enable','college_fee_scholarship.disable'];
        $ids = DB::table('permissions')->whereIn('code',$codes)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id',$ids)->delete();
        DB::table('permissions')->whereIn('code',$codes)->delete();
        Schema::dropIfExists('fee_scholarship_scheme_categories');
        Schema::dropIfExists('fee_scholarship_scheme_heads');
        Schema::dropIfExists('fee_scholarship_schemes');
    }
};
