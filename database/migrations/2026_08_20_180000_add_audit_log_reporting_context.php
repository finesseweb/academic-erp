<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('scope_type', 30)->nullable()->after('resource_id');
            $table->string('scope_reference', 100)->nullable()->after('scope_type');
            $table->uuid('request_id')->nullable()->after('scope_reference');
            $table->string('user_agent', 500)->nullable()->after('ip_address');
            $table->index(['event', 'created_at'], 'audit_logs_event_date_idx');
            $table->index(['scope_type', 'scope_reference', 'created_at'], 'audit_logs_scope_date_idx');
            $table->index('created_at', 'audit_logs_date_idx');
        });

        $now = now();
        $permissionId = DB::table('permissions')->insertGetId([
            'code' => 'audit.view', 'module' => 'Audit & Security', 'resource' => 'audit',
            'action' => 'view', 'description' => 'View immutable audit history',
            'is_sensitive' => true, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now,
        ]);
        $superAdminRoleId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');
        if ($superAdminRoleId) {
            DB::table('role_permissions')->insert(['role_id' => $superAdminRoleId, 'permission_id' => $permissionId, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        $permissionIds = DB::table('permissions')->where('code', 'audit.view')->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
        DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_event_date_idx');
            $table->dropIndex('audit_logs_scope_date_idx');
            $table->dropIndex('audit_logs_date_idx');
            $table->dropColumn(['scope_type', 'scope_reference', 'request_id', 'user_agent']);
        });
    }
};
