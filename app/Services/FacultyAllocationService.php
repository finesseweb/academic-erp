<?php

namespace App\Services;

use App\Models\College;
use App\Models\CourseOffering;
use App\Models\FacultyAllocation;
use App\Models\Section;
use App\Models\TimetableEntry;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FacultyAllocationService
{
    public function save(College $college, array $data, int $actorId, ?string $ip, ?FacultyAllocation $allocation = null): FacultyAllocation
    {
        $offering = $this->offering($college, (int) $data['course_offering_id']);
        $faculty = $this->faculty($college, (int) $data['faculty_user_id']);
        $sectionId = $data['delivery_scope'] === 'SECTION' ? (int) ($data['section_id'] ?? 0) : null;
        if ($sectionId) {
            $valid = Section::whereKey($sectionId)->where('batch_id', $offering->batch_id)->where('status', 'ACTIVE')->exists();
            if (! $valid) {
                throw ValidationException::withMessages(['section_id' => 'Select an active Section belonging to the Course Offering Batch.']);
            }
        } elseif ($data['delivery_scope'] === 'SECTION') {
            throw ValidationException::withMessages(['section_id' => 'Select the Section for this section-specific allocation.']);
        }
        $duplicate = FacultyAllocation::where('course_offering_id', $offering->id)->where('faculty_user_id', $faculty->id)
            ->where(fn ($q) => $sectionId ? $q->where('section_id', $sectionId) : $q->whereNull('section_id'))
            ->when($allocation, fn ($q) => $q->whereKeyNot($allocation->id))->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['faculty_user_id' => 'This faculty member is already allocated at the selected delivery scope.']);
        }
        if ($allocation && $allocation->status === 'ACTIVE') {
            throw ValidationException::withMessages(['allocation' => 'Deactivate the allocation before editing it.']);
        }

        return DB::transaction(function () use ($allocation, $data, $offering, $faculty, $sectionId, $college, $actorId, $ip) {
            $before = $allocation?->toArray();
            $values = ['course_offering_id' => $offering->id, 'section_id' => $sectionId, 'faculty_user_id' => $faculty->id,
                'teaching_role' => $data['teaching_role'], 'weekly_load' => $data['weekly_load'] ?? null, 'notes' => $data['notes'] ?? null, 'updated_by' => $actorId];
            if ($allocation) {
                $allocation->update($values);
            } else {
                $allocation = FacultyAllocation::create($values + ['status' => 'INACTIVE', 'created_by' => $actorId]);
            }
            $this->audit($before ? 'FACULTY_ALLOCATION_UPDATED' : 'FACULTY_ALLOCATION_CREATED', $allocation, $college, $actorId, $ip, $before, $allocation->fresh()->toArray());

            return $allocation;
        });
    }

    public function changeStatus(College $college, FacultyAllocation $allocation, string $status, int $actorId, ?string $ip): void
    {
        $offering = $this->offering($college, $allocation->course_offering_id);
        $this->faculty($college, $allocation->faculty_user_id);
        if ($status === 'ACTIVE') {
            if ($college->status !== 'ACTIVE' || $offering->status !== 'ACTIVE' || $offering->batch?->status !== 'ACTIVE' || $offering->batch?->offering?->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['status' => 'Activation requires an ACTIVE College, Course Offering, Batch and Program Offering.']);
            }
            if ($allocation->section_id && ! Section::whereKey($allocation->section_id)->where('batch_id', $offering->batch_id)->where('status', 'ACTIVE')->exists()) {
                throw ValidationException::withMessages(['status' => 'A Section-scoped allocation requires an ACTIVE Section in the same Batch.']);
            }
        }
        if ($status === 'INACTIVE' && TimetableEntry::where('faculty_allocation_id', $allocation->id)->where('status', 'ACTIVE')->exists()) {
            throw ValidationException::withMessages(['status' => 'Deactivate active Timetable entries before deactivating this Faculty Allocation.']);
        }
        if ($allocation->status === $status) {
            return;
        }
        DB::transaction(function () use ($allocation, $status, $college, $actorId, $ip) {
            $before = ['status' => $allocation->status];
            $allocation->update(['status' => $status, 'updated_by' => $actorId]);
            $this->audit($status === 'ACTIVE' ? 'FACULTY_ALLOCATION_ACTIVATED' : 'FACULTY_ALLOCATION_DEACTIVATED', $allocation, $college, $actorId, $ip, $before, ['status' => $status]);
        });
    }

    private function offering(College $college, int $id): CourseOffering
    {
        $row = CourseOffering::with('batch.offering')->whereKey($id)->whereHas('batch.offering', fn ($q) => $q->where('college_id', $college->id))->first();
        if (! $row) {
            throw ValidationException::withMessages(['course_offering_id' => 'Select a Course Offering belonging to this College.']);
        }

        return $row;
    }

    private function faculty(College $college, int $id): User
    {
        $row = User::whereKey($id)->where('primary_college_id', $college->id)->where('account_type', 'COLLEGE_STAFF')->where('status', 'ACTIVE')
            ->whereHas('roles', fn ($q) => $q->where('roles.status', 'ACTIVE')->where('user_roles.status', 'ACTIVE')->where('user_roles.scope_type', 'COLLEGE')
                ->where('user_roles.scope_reference', "college:{$college->id}")->where(fn ($r) => $r->whereNull('user_roles.effective_from')->orWhere('user_roles.effective_from', '<=', now()))
                ->where(fn ($r) => $r->whereNull('user_roles.effective_until')->orWhere('user_roles.effective_until', '>=', now()))
                ->whereHas('permissions', fn ($permission) => $permission->where('permissions.code', 'college_faculty_allocation.eligible')->where('permissions.status', 'ACTIVE')))->first();
        if (! $row) {
            throw ValidationException::withMessages(['faculty_user_id' => 'Select an active College Staff user with an active Faculty role in this College.']);
        }

        return $row;
    }

    private function audit(string $event, FacultyAllocation $row, College $college, int $actorId, ?string $ip, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert(['actor_user_id' => $actorId, 'event' => $event, 'resource_type' => 'FacultyAllocation', 'resource_id' => $row->id, 'scope_type' => 'COLLEGE', 'scope_reference' => 'college:'.$college->id, 'before' => $before ? json_encode($before) : null, 'after' => $after ? json_encode($after) : null, 'ip_address' => $ip, 'created_at' => now()]);
    }
}
