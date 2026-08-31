<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        ['college_admission_form.delete', 'delete', 'Delete DRAFT Admission Form templates', true],
        ['college_admission_form.step_update', 'step_update', 'Edit Admission Form steps', false],
        ['college_admission_form.step_delete', 'step_delete', 'Delete Admission Form steps', true],
        ['college_admission_form.panel_create', 'panel_create', 'Add optional panels/sections inside Admission Form steps', false],
        ['college_admission_form.panel_update', 'panel_update', 'Edit Admission Form panels/sections', false],
        ['college_admission_form.panel_delete', 'panel_delete', 'Delete Admission Form panels/sections', true],
        ['college_admission_form.field_update', 'field_update', 'Edit Admission Form fields and rules', false],
        ['college_admission_form.field_delete', 'field_delete', 'Delete Admission Form fields', true],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('college_admission_form_panels')) {
            Schema::create('college_admission_form_panels', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('college_admission_form_step_id');
                $table->string('title', 160);
                $table->string('code', 80);
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('display_order')->default(0);
                $table->boolean('is_locked')->default(false);
                $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
                $table->timestamps();
                $table->foreign('college_admission_form_step_id', 'cafp_step_fk')->references('id')->on('college_admission_form_steps')->cascadeOnDelete();
                $table->unique(['college_admission_form_step_id', 'code'], 'cafp_step_code_uq');
                $table->index(['college_admission_form_step_id', 'status', 'display_order'], 'cafp_step_order_idx');
            });
        }

        if (! Schema::hasColumn('college_admission_form_fields', 'college_admission_form_panel_id')) {
            Schema::table('college_admission_form_fields', function (Blueprint $table) {
                $table->unsignedBigInteger('college_admission_form_panel_id')->nullable()->after('college_admission_form_step_id');
                $table->foreign('college_admission_form_panel_id', 'caff_panel_fk')->references('id')->on('college_admission_form_panels')->nullOnDelete();
                $table->index(['college_admission_form_panel_id', 'display_order'], 'caff_panel_order_idx');
            });
        }

        $now = now();
        foreach ($this->permissions as [$code, $action, $description, $sensitive]) {
            DB::table('permissions')->updateOrInsert(['code'=>$code], [
                'module'=>'Admission','resource'=>'college_admission_form','action'=>$action,'description'=>$description,
                'is_sensitive'=>$sensitive,'is_college_delegable'=>true,'status'=>'ACTIVE','created_at'=>$now,'updated_at'=>$now,
            ]);
        }

        // College Administrator follows the existing hierarchy pattern: Admission Form permissions are checked by default.
        $allCodes = array_merge([
            'college_admission_form.view','college_admission_form.create','college_admission_form.update','college_admission_form.status',
            'college_admission_form.step_create','college_admission_form.field_create','college_admission_form.map','college_application_fee.manage',
        ], array_column($this->permissions, 0));
        $permissionIds = DB::table('permissions')->whereIn('code', $allCodes)->where('status','ACTIVE')->pluck('id');
        foreach (DB::table('roles')->whereIn('code',['SUPER_ADMIN','COLLEGE_ADMIN'])->pluck('id') as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(['role_id'=>$roleId,'permission_id'=>$permissionId], ['created_at'=>$now,'updated_at'=>$now]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('college_admission_form_fields', 'college_admission_form_panel_id')) {
            Schema::table('college_admission_form_fields', function (Blueprint $table) {
                $table->dropForeign('caff_panel_fk');
                $table->dropIndex('caff_panel_order_idx');
                $table->dropColumn('college_admission_form_panel_id');
            });
        }
        Schema::dropIfExists('college_admission_form_panels');
    }
};
