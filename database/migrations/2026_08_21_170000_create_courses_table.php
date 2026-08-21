<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $t) {
            $t->id();
            $t->foreignId('university_id')->constrained()->restrictOnDelete();
            $t->foreignId('course_category_id')->constrained('course_categories')->restrictOnDelete();
            $t->foreignId('course_type_id')->constrained('course_types')->restrictOnDelete();
            $t->string('name', 150);
            $t->string('code', 40);
            $t->text('description')->nullable();
            $t->unsignedSmallInteger('display_order')->default(0);
            $t->string('status', 20)->default('ACTIVE');
            $t->timestamps();

            $t->unique(['university_id', 'code']);
            $t->index(
                ['university_id', 'course_category_id', 'course_type_id', 'status', 'display_order'],
                'course_scope_order_idx'
            );
        });

        $now = now();
        $permissions = [
            ['course.view', 'view', false],
            ['course.create', 'create', false],
            ['course.update', 'update', false],
            ['course.disable', 'disable', true],
        ];

        foreach ($permissions as [$code, $action, $sensitive]) {
            $id = DB::table('permissions')->insertGetId([
                'code' => $code,
                'module' => 'Academic Structure',
                'resource' => 'course',
                'action' => $action,
                'description' => ucfirst($action).' courses / subjects',
                'is_sensitive' => $sensitive,
                'is_college_delegable' => false,
                'status' => 'ACTIVE',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach (DB::table('roles')->where('code', 'SUPER_ADMIN')->pluck('id') as $roleId) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->where('resource', 'course')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        Schema::dropIfExists('courses');
    }
};
