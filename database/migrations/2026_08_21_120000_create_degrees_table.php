<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('degrees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('university_id')->constrained()->restrictOnDelete();
            $table->foreignId('degree_level_id')->constrained()->restrictOnDelete();
            $table->string('name', 120);
            $table->string('code', 40);
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('typical_duration_years')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->string('status', 20)->default('ACTIVE');
            $table->timestamps();
            $table->unique(['university_id', 'code']);
            $table->index(['university_id', 'degree_level_id', 'status', 'display_order']);
        });
        $this->permissions();
    }

    private function permissions(): void
    {
        $now = now();
        foreach ([['degree.view', 'view', false], ['degree.create', 'create', false], ['degree.update', 'update', false], ['degree.disable', 'disable', true]] as [$code, $action, $sensitive]) {
            $id = DB::table('permissions')->insertGetId(['code' => $code, 'module' => 'Academic Structure', 'resource' => 'degree', 'action' => $action, 'description' => ucfirst($action).' degrees', 'is_sensitive' => $sensitive, 'is_college_delegable' => false, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now]);
            foreach (DB::table('roles')->where('code', 'SUPER_ADMIN')->pluck('id') as $roleId) {
                DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('resource', 'degree')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('degrees');
    }
};
