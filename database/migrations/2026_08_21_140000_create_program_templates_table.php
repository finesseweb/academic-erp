<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('program_templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('university_id')->constrained()->restrictOnDelete();
            $t->foreignId('degree_id')->constrained()->restrictOnDelete();
            $t->foreignId('discipline_id')->nullable()->constrained('academic_disciplines')->restrictOnDelete();
            $t->string('name', 150);
            $t->string('code', 40);
            $t->string('term_structure', 20)->default('SEMESTER');
            $t->unsignedTinyInteger('duration_terms');
            $t->text('description')->nullable();
            $t->unsignedSmallInteger('display_order')->default(0);
            $t->string('status', 20)->default('ACTIVE');
            $t->timestamps();
            $t->unique(['university_id', 'code']);
            $t->index(['university_id', 'degree_id', 'status', 'display_order'], 'program_tpl_scope_order_idx');
        });
        $now = now();
        foreach ([['program_template.view', 'view', false], ['program_template.create', 'create', false], ['program_template.update', 'update', false], ['program_template.disable', 'disable', true]] as [$code,$action,$s]) {
            $id = DB::table('permissions')->insertGetId(['code' => $code, 'module' => 'Academic Structure', 'resource' => 'program_template', 'action' => $action, 'description' => ucfirst($action).' program templates', 'is_sensitive' => $s, 'is_college_delegable' => false, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now]);
            foreach (DB::table('roles')->where('code', 'SUPER_ADMIN')->pluck('id') as $role) {
                DB::table('role_permissions')->insert(['role_id' => $role, 'permission_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('resource', 'program_template')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('program_templates');
    }
};
