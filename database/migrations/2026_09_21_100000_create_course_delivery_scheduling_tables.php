<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $permissions = [
        ['college_room.view', 'view', 'View Rooms', false], ['college_room.manage', 'manage', 'Manage Rooms', false],
        ['college_timetable.view', 'view', 'View Timetable', false], ['college_timetable.manage', 'manage', 'Manage Timetable', false],
        ['college_timetable.enable', 'enable', 'Activate Timetable Entries', true], ['college_timetable.disable', 'disable', 'Deactivate Timetable Entries', true],
        ['college_class_schedule.view', 'view', 'View Class Schedule', false], ['college_class_schedule.manage', 'manage', 'Manage Class Schedule', false],
        ['college_class_schedule.status', 'status', 'Update Class Schedule Status', true],
    ];

    public function up(): void
    {
        Schema::create('college_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained('colleges')->restrictOnDelete();
            $table->string('code', 50);
            $table->string('name', 150);
            $table->string('building', 150)->nullable();
            $table->string('floor', 50)->nullable();
            $table->enum('room_type', ['CLASSROOM', 'LAB', 'SEMINAR', 'OTHER'])->default('CLASSROOM');
            $table->unsignedInteger('capacity')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['college_id', 'code'], 'college_rooms_college_code_uq');
            $table->index(['college_id', 'status'], 'college_rooms_college_status_idx');
        });
        Schema::create('timetable_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faculty_allocation_id')->constrained('faculty_allocations')->restrictOnDelete();
            $table->foreignId('room_id')->nullable()->constrained('college_rooms')->restrictOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('INACTIVE');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['faculty_allocation_id', 'status'], 'timetable_faculty_status_idx');
            $table->index(['room_id', 'day_of_week', 'status'], 'timetable_room_day_status_idx');
        });
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('timetable_entry_id')->constrained('timetable_entries')->restrictOnDelete();
            $table->date('class_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('room_id')->nullable()->constrained('college_rooms')->restrictOnDelete();
            $table->enum('status', ['SCHEDULED', 'COMPLETED', 'CANCELLED'])->default('SCHEDULED');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['timetable_entry_id', 'class_date'], 'class_schedules_entry_date_uq');
            $table->index(['class_date', 'status'], 'class_schedules_date_status_idx');
        });
        $now = now();
        foreach ($this->permissions as [$code,$action,$description,$sensitive]) {
            DB::table('permissions')->updateOrInsert(['code' => $code], [
                'resource' => str_contains($code, 'class_schedule') ? 'college_class_schedule' : (str_contains($code, 'timetable') ? 'college_timetable' : 'college_room'),
                'action' => $action, 'module' => 'Course Delivery', 'description' => $description, 'is_sensitive' => $sensitive, 'is_college_delegable' => true, 'status' => 'ACTIVE', 'created_at' => $now, 'updated_at' => $now]);
        }
        $ids = DB::table('permissions')->whereIn('code', array_column($this->permissions, 0))->pluck('id');
        foreach (['SUPER_ADMIN', 'COLLEGE_ADMIN'] as $roleCode) {
            $roleId = DB::table('roles')->where('code', $roleCode)->where('status', 'ACTIVE')->value('id');
            if (! $roleId) {
                continue;
            } foreach ($ids as $id) {
                DB::table('role_permissions')->updateOrInsert(['role_id' => $roleId, 'permission_id' => $id], ['created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
        Schema::dropIfExists('timetable_entries');
        Schema::dropIfExists('college_rooms');
        $codes = array_column($this->permissions, 0);
        $ids = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        DB::table('role_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id',$ids)->delete();
    }
};
