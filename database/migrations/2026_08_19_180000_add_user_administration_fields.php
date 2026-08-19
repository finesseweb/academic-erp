<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile', 30)->nullable()->after('email');
            $table->string('account_type', 40)->default('UNIVERSITY_STAFF')->after('mobile');
            $table->string('status', 20)->default('ACTIVE')->after('account_type');
            $table->timestamp('last_login_at')->nullable()->after('email_verified_at');
            $table->index(['status', 'account_type', 'created_at']);
        });
        $now = now();
        $roleId = DB::table('roles')->where('code', 'SUPER_ADMIN')->value('id');
        foreach ([['user.view', 'view', 'View user accounts'], ['user.create', 'create', 'Create user accounts'], ['user.update', 'update', 'Update user accounts'], ['user.disable', 'disable', 'Disable user accounts'], ['user.enable', 'enable', 'Enable user accounts'], ['user.reset_password', 'reset_password', 'Initiate administrative password reset']] as [$code,$action,$description]) {
            $id = DB::table('permissions')->insertGetId(['code' => $code, 'module' => 'Access & Security', 'resource' => 'user', 'action' => $action, 'description' => $description, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now]);
            if ($roleId) {
                DB::table('role_permissions')->insert(['role_id' => $roleId, 'permission_id' => $id, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('code', ['user.view', 'user.create', 'user.update', 'user.disable', 'user.enable', 'user.reset_password'])->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['status', 'account_type', 'created_at']);
            $table->dropColumn(['mobile', 'account_type', 'status', 'last_login_at']);
        });
    }
};
