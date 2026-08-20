<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_disciplines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('university_id')->constrained()->restrictOnDelete();
            $t->foreignId('parent_id')->nullable()->constrained('academic_disciplines')->restrictOnDelete();
            $t->string('kind', 20)->default('DISCIPLINE');
            $t->string('name', 120);
            $t->string('code', 40);
            $t->text('description')->nullable();
            $t->unsignedSmallInteger('display_order')->default(0);
            $t->string('status', 20)->default('ACTIVE');
            $t->timestamps();
            $t->unique(['university_id', 'code']);
            $t->index(['university_id', 'kind', 'status', 'display_order'], 'acad_disc_scope_order_idx');
        });
        $now = now();
        foreach ([['discipline.view', 'view', false], ['discipline.create', 'create', false], ['discipline.update', 'update', false], ['discipline.disable', 'disable', true]] as [$code,$action,$s]) {
            $id = DB::table('permissions')->insertGetId(['code' => $code, 'module' => 'Academic Structure', 'resource' => 'discipline', 'action' => $action, 'description' => ucfirst($action).' disciplines and specializations', 'is_sensitive' => $s, 'is_college_delegable' => false, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now]);
            foreach (DB::table('roles')->where('code', 'SUPER_ADMIN')->pluck('id') as $role) {
                DB::table('role_permissions')->insert(['role_id' => $role, 'permission_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('resource', 'discipline')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('academic_disciplines');
    }
};
