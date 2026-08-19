<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('code', 80)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_system_role')->default(false);
            $table->string('owner_scope_type', 30)->default('GLOBAL');
            $table->string('owner_scope_reference', 100)->default('global');
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 120)->unique();
            $table->string('module', 80);
            $table->string('resource', 80);
            $table->string('action', 40);
            $table->string('description', 255);
            $table->boolean('is_sensitive')->default(false);
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamps();
            $table->unique(['resource', 'action']);
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->foreignId('permission_id')->constrained()->restrictOnDelete();
            $table->primary(['role_id', 'permission_id']);
            $table->timestamps();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('role_id')->constrained()->restrictOnDelete();
            $table->string('scope_type', 30)->default('UNIVERSITY');
            $table->string('scope_reference', 100)->default('university');
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_until')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'role_id', 'scope_type', 'scope_reference']);
            $table->index(['user_id', 'scope_type', 'scope_reference']);
        });

        Schema::create('universities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 180);
            $table->string('code', 30)->unique();
            $table->string('short_name', 60)->nullable();
            $table->date('established_on')->nullable();
            $table->string('university_type', 60)->nullable();
            $table->string('accreditation', 120)->nullable();
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
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 100);
            $table->string('resource_type', 100);
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['resource_type', 'resource_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
        });

        $now = now();
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'Super Administrator', 'code' => 'SUPER_ADMIN',
            'is_system_role' => true, 'owner_scope_type' => 'GLOBAL', 'owner_scope_reference' => 'global',
            'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now,
        ]);

        foreach ([
            ['university.view', 'view', 'View the University profile'],
            ['university.update', 'update', 'Update the University profile'],
        ] as [$code, $action, $description]) {
            $permissionId = DB::table('permissions')->insertGetId([
                'code' => $code, 'module' => 'University', 'resource' => 'university', 'action' => $action,
                'description' => $description, 'status' => 'ACTIVE',
                'created_at' => $now, 'updated_at' => $now,
            ]);
            DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $permissionId, 'created_at' => $now, 'updated_at' => $now]);
        }

        $userId = DB::table('users')->orderBy('id')->value('id');
        if ($userId) {
            DB::table('user_roles')->insert([
                'user_id' => $userId, 'role_id' => $roleId, 'scope_type' => 'UNIVERSITY',
                'scope_reference' => 'university', 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        DB::table('universities')->insert([
            'name' => config('app.name', 'Academic ERP University'),
            'code' => 'UNIVERSITY', 'country' => 'India', 'timezone' => 'Asia/Kolkata',
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('universities');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
