<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('code', 40);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamps();

            $table->unique(['university_id', 'code']);
            $table->index(
                ['university_id', 'status', 'display_order'],
                'course_type_scope_order_idx',
            );
        });

        $now = now();
        $permissions = [
            ['course_type.view', 'view', false],
            ['course_type.create', 'create', false],
            ['course_type.update', 'update', false],
            ['course_type.disable', 'disable', true],
        ];

        foreach ($permissions as [$code, $action, $sensitive]) {
            $permissionId = DB::table('permissions')->insertGetId([
                'code' => $code,
                'module' => 'Academic Structure',
                'resource' => 'course_type',
                'action' => $action,
                'description' => ucfirst($action).' course types',
                'is_sensitive' => $sensitive,
                'is_college_delegable' => false,
                'status' => 'ACTIVE',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (DB::table('roles')->where('code', 'SUPER_ADMIN')->pluck('id') as $roleId) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')
            ->where('resource', 'course_type')
            ->pluck('id');

        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        Schema::dropIfExists('course_types');
    }
};
