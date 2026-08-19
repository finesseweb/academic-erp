<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->restrictOnDelete();
            $table->string('name', 180);
            $table->string('code', 30)->unique();
            $table->string('affiliation_type', 60);
            $table->string('status', 20)->default('ACTIVE');
            $table->string('official_email', 180)->nullable();
            $table->string('official_phone', 30)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('address_line_1', 180)->nullable();
            $table->string('address_line_2', 180)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('India');
            $table->string('timezone', 80)->default('Asia/Kolkata');
            $table->foreignId('principal_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['university_id', 'status', 'name']);
            $table->index(['university_id', 'affiliation_type', 'name']);
        });

        $now = now();
        $roleId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');
        foreach ([
            ['college.view', 'view', 'View affiliated Colleges'],
            ['college.create', 'create', 'Create affiliated Colleges'],
            ['college.update', 'update', 'Update affiliated Colleges'],
            ['college.disable', 'disable', 'Activate or deactivate affiliated Colleges'],
        ] as [$code, $action, $description]) {
            $permissionId = DB::table('permissions')->insertGetId([
                'code' => $code, 'module' => 'University', 'resource' => 'college',
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
        Schema::dropIfExists('colleges');
        $permissionIds = DB::table('permissions')->whereIn('code', ['college.view', 'college.create', 'college.update', 'college.disable'])->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
    }
};
