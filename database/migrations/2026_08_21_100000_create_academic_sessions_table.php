<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->restrictOnDelete();
            $table->string('name', 100);
            $table->string('code', 40);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 20)->default('PLANNED');
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->unique(['university_id', 'code']);
            $table->index(['university_id', 'status']);
            $table->index(['university_id', 'is_current']);
        });

        $now = now();
        foreach ([
            ['code' => 'academic_session.view', 'action' => 'view', 'description' => 'View academic sessions', 'sensitive' => false],
            ['code' => 'academic_session.create', 'action' => 'create', 'description' => 'Create academic sessions', 'sensitive' => false],
            ['code' => 'academic_session.update', 'action' => 'update', 'description' => 'Update academic sessions', 'sensitive' => false],
            ['code' => 'academic_session.close', 'action' => 'close', 'description' => 'Close or archive academic sessions', 'sensitive' => true],
            ['code' => 'academic_session.set_current', 'action' => 'set_current', 'description' => 'Set the current academic session', 'sensitive' => true],
        ] as $definition) {
            $id = DB::table('permissions')->insertGetId(['code' => $definition['code'], 'module' => 'Academic Structure', 'resource' => 'academic_session', 'action' => $definition['action'], 'description' => $definition['description'], 'is_sensitive' => $definition['sensitive'], 'is_college_delegable' => false, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now]);
            foreach (DB::table('roles')->where('code', 'SUPER_ADMIN')->pluck('id') as $roleId) {
                DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('resource', 'academic_session')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('academic_sessions');
    }
};
