<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('university_id')->constrained()->restrictOnDelete();
            $t->string('name', 120);
            $t->string('code', 40);
            $t->string('category_group', 20)->default('OTHER');
            $t->text('description')->nullable();
            $t->unsignedSmallInteger('display_order')->default(0);
            $t->string('status', 20)->default('ACTIVE');
            $t->timestamps();
            $t->unique(['university_id', 'code']);
            $t->index(['university_id', 'category_group', 'status', 'display_order'], 'course_cat_scope_order_idx');
        });
        $now = now();
        foreach ([['course_category.view', 'view', false], ['course_category.create', 'create', false], ['course_category.update', 'update', false], ['course_category.disable', 'disable', true]] as [$code,$action,$s]) {
            $id = DB::table('permissions')->insertGetId(['code' => $code, 'module' => 'Academic Structure', 'resource' => 'course_category', 'action' => $action, 'description' => ucfirst($action).' course categories', 'is_sensitive' => $s, 'is_college_delegable' => false, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now]);
            foreach (DB::table('roles')->where('code', 'SUPER_ADMIN')->pluck('id') as $role) {
                DB::table('role_permissions')->insert(['role_id' => $role, 'permission_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('resource', 'course_category')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        Schema::dropIfExists('course_categories');
    }
};
