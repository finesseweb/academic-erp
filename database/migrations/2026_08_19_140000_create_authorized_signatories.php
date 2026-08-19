<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('authorized_signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->restrictOnDelete();
            $table->string('full_name', 180);
            $table->string('designation', 120);
            $table->string('authority_type', 40);
            $table->string('email', 180)->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->string('status', 20)->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['university_id', 'status', 'effective_from'], 'signatory_university_status_date_idx');
            $table->index(['university_id', 'authority_type', 'status'], 'signatory_university_authority_idx');
        });

        $now = now();
        $roleId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');
        foreach ([
            ['authorized_signatory.view', 'view', 'View authorized signatories'],
            ['authorized_signatory.create', 'create', 'Create authorized signatories'],
            ['authorized_signatory.update', 'update', 'Update authorized signatories'],
            ['authorized_signatory.disable', 'disable', 'Activate or deactivate authorized signatories'],
        ] as [$code, $action, $description]) {
            $permissionId = DB::table('permissions')->insertGetId([
                'code' => $code, 'module' => 'University', 'resource' => 'authorized_signatory',
                'action' => $action, 'description' => $description, 'status' => 'ACTIVE',
                'created_at' => $now, 'updated_at' => $now,
            ]);
            if ($roleId) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleId, 'permission_id' => $permissionId,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('authorized_signatories');
        $codes = ['authorized_signatory.view', 'authorized_signatory.create', 'authorized_signatory.update', 'authorized_signatory.disable'];
        $permissionIds = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
