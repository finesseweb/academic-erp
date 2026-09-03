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
            $table->foreignId('created_by_user_id')->nullable()->after('primary_college_id')->constrained('users')->nullOnDelete();
            $table->string('created_by_scope_type', 20)->default('UNIVERSITY')->after('created_by_user_id');
            $table->index(['created_by_scope_type', 'primary_college_id', 'status'], 'users_creator_scope_college_status_idx');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->foreignId('created_by_user_id')->nullable()->after('owner_scope_reference')->constrained('users')->nullOnDelete();
            $table->string('created_by_scope_type', 20)->default('UNIVERSITY')->after('created_by_user_id');
            $table->index(['created_by_scope_type', 'owner_scope_type', 'owner_scope_reference', 'status'], 'roles_creator_owner_status_idx');
        });

        $actorScope = static function (?int $actorId): string {
            if (! $actorId) {
                return 'UNIVERSITY';
            }

            return DB::table('user_roles')
                ->where('user_id', $actorId)
                ->where('scope_type', 'UNIVERSITY')
                ->exists() ? 'UNIVERSITY' : 'COLLEGE';
        };

        DB::table('users')->orderBy('id')->chunkById(200, function ($users) use ($actorScope) {
            foreach ($users as $user) {
                if ($user->account_type === 'APPLICANT') {
                    DB::table('users')->where('id', $user->id)->update(['created_by_scope_type' => 'APPLICANT']);
                    continue;
                }

                $audit = DB::table('audit_logs')
                    ->where('event', 'USER_CREATED')
                    ->where('resource_type', 'User')
                    ->where('resource_id', $user->id)
                    ->orderBy('id')
                    ->first(['actor_user_id']);

                DB::table('users')->where('id', $user->id)->update([
                    'created_by_user_id' => $audit?->actor_user_id,
                    'created_by_scope_type' => $actorScope($audit?->actor_user_id),
                ]);
            }
        });

        DB::table('roles')->orderBy('id')->chunkById(200, function ($roles) use ($actorScope) {
            foreach ($roles as $role) {
                $audit = DB::table('audit_logs')
                    ->where('event', 'ROLE_CREATED')
                    ->where('resource_type', 'Role')
                    ->where('resource_id', $role->id)
                    ->orderBy('id')
                    ->first(['actor_user_id']);

                DB::table('roles')->where('id', $role->id)->update([
                    'created_by_user_id' => $audit?->actor_user_id,
                    'created_by_scope_type' => $actorScope($audit?->actor_user_id),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex('roles_creator_owner_status_idx');
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn(['created_by_user_id', 'created_by_scope_type']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_creator_scope_college_status_idx');
            $table->dropForeign(['created_by_user_id']);
            $table->dropColumn(['created_by_user_id', 'created_by_scope_type']);
        });
    }
};
