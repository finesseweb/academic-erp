<?php

namespace App\Services;

use App\Models\ClassSchedule;
use App\Models\College;
use App\Models\CollegeRoom;
use App\Models\FacultyAllocation;
use App\Models\TimetableEntry;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseDeliverySchedulingService
{
    /** @param array<string, mixed> $data */
    public function saveRoom(College $college, array $data, int $actor, ?CollegeRoom $room = null): CollegeRoom
    {
        if ($room && $room->college_id !== $college->id) {
            abort(404);
        }
        $duplicate = CollegeRoom::where('college_id', $college->id)->where('code', strtoupper($data['code']))->when($room, fn ($q) => $q->whereKeyNot($room->id))->exists();
        if ($duplicate) {
            throw ValidationException::withMessages(['code' => 'Room code already exists in this College.']);
        }
        if ($room && $data['status'] === 'INACTIVE' && TimetableEntry::where('room_id', $room->id)->where('status', 'ACTIVE')->exists()) {
            throw ValidationException::withMessages(['status' => 'Deactivate active Timetable entries before deactivating this Room.']);
        }
        $before = $room?->toArray();
        $values = [...$data, 'college_id' => $college->id, 'code' => strtoupper($data['code']), 'updated_by' => $actor];
        if ($room) {
            $room->update($values);
        } else {
            $room = CollegeRoom::create($values + ['created_by' => $actor]);
        } $this->audit($before ? 'ROOM_UPDATED' : 'ROOM_CREATED', $room, $college, $actor, $before, $room->fresh()->toArray());

        return $room;
    }

    /** @param array<string, mixed> $data */
    public function saveTimetable(College $college, array $data, int $actor, ?TimetableEntry $entry = null): TimetableEntry
    {
        if ($entry && $entry->status === 'ACTIVE') {
            throw ValidationException::withMessages(['timetable' => 'Deactivate the Timetable entry before editing.']);
        }
        $allocation = $this->allocation($college, (int) $data['faculty_allocation_id']);
        if ($allocation->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['faculty_allocation_id' => 'Only an ACTIVE Faculty Allocation can be used in Timetable.']);
        }
        $room = $this->room($college, $data['room_id'] ?? null);
        if ($data['start_time'] >= $data['end_time']) {
            throw ValidationException::withMessages(['end_time' => 'End time must be after start time.']);
        } if (! empty($data['effective_until']) && $data['effective_until'] < $data['effective_from']) {
            throw ValidationException::withMessages(['effective_until' => 'Effective until must be on or after effective from.']);
        } $this->assertTimetableConflict($allocation, $room, (int) $data['day_of_week'], $data['start_time'], $data['end_time'], $data['effective_from'], $data['effective_until'] ?? null, $entry?->id);
        $before = $entry?->toArray();
        $values = [...$data, 'room_id' => $room?->id, 'updated_by' => $actor];
        if ($entry) {
            $entry->update($values);
        } else {
            $entry = TimetableEntry::create($values + ['status' => 'INACTIVE', 'created_by' => $actor]);
        } $this->audit($before ? 'TIMETABLE_ENTRY_UPDATED' : 'TIMETABLE_ENTRY_CREATED', $entry, $college, $actor, $before, $entry->fresh()->toArray());

        return $entry;
    }

    public function timetableStatus(College $college, TimetableEntry $entry, string $status, int $actor): void
    {
        $allocation = $this->allocation($college, $entry->faculty_allocation_id);
        if ($status === 'ACTIVE') {
            if ($allocation->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['status' => 'Timetable activation requires an ACTIVE Faculty Allocation.']);
            }$room = $this->room($college, $entry->room_id);
            if ($room && $room->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['status' => 'Timetable activation requires an ACTIVE Room.']);
            }
            $this->assertTimetableConflict($allocation, $room, $entry->day_of_week, $entry->start_time, $entry->end_time, Carbon::parse($entry->effective_from)->toDateString(), $entry->effective_until ? Carbon::parse($entry->effective_until)->toDateString() : null, $entry->id);
            $this->assertWeeklyLoad($allocation, $entry);
        } if ($entry->status === $status) {
            return;
        } $before = ['status' => $entry->status];
        $entry->update(['status' => $status, 'updated_by' => $actor]);
        $this->audit($status === 'ACTIVE' ? 'TIMETABLE_ENTRY_ACTIVATED' : 'TIMETABLE_ENTRY_DEACTIVATED', $entry, $college, $actor, $before, ['status' => $status]);
    }

    /** @param array<string, mixed> $data */
    public function createClass(College $college, array $data, int $actor): ClassSchedule
    {
        $entry = $this->entry($college, (int) $data['timetable_entry_id']);
        if ($entry->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['timetable_entry_id' => 'Only an ACTIVE Timetable entry can schedule a class.']);
        }
        if ($this->allocation($college, $entry->faculty_allocation_id)->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['timetable_entry_id' => 'The linked Faculty Allocation is inactive.']);
        }
        $date = Carbon::parse($data['class_date']);
        if ($date->dayOfWeekIso !== $entry->day_of_week) {
            throw ValidationException::withMessages(['class_date' => 'Class date must match the Timetable weekday.']);
        } if ($date->lt($entry->effective_from) || ($entry->effective_until && $date->gt($entry->effective_until))) {
            throw ValidationException::withMessages(['class_date' => 'Class date is outside the Timetable effective period.']);
        }
        if (ClassSchedule::where('timetable_entry_id', $entry->id)->whereDate('class_date', $data['class_date'])->exists()) {
            throw ValidationException::withMessages(['class_date' => 'A class is already scheduled from this Timetable entry on the selected date.']);
        }
        $row = ClassSchedule::create(['timetable_entry_id' => $entry->id, 'class_date' => $data['class_date'], 'start_time' => $entry->start_time, 'end_time' => $entry->end_time, 'room_id' => $entry->room_id, 'status' => 'SCHEDULED', 'notes' => $data['notes'] ?? null, 'created_by' => $actor, 'updated_by' => $actor]);
        $this->audit('CLASS_SCHEDULED', $row, $college, $actor, null, $row->toArray());

        return $row;
    }

    public function classStatus(College $college, ClassSchedule $row, string $status, int $actor): void
    {
        $this->entry($college, $row->timetable_entry_id);
        if ($row->status === $status) {
            return;
        }$before = ['status' => $row->status];
        $row->update(['status' => $status, 'updated_by' => $actor]);
        $this->audit('CLASS_SCHEDULE_STATUS_UPDATED', $row, $college, $actor, $before, ['status' => $status]);
    }

    private function allocation(College $college, int $id): FacultyAllocation
    {
        $row = FacultyAllocation::with('courseOffering.batch.offering')->whereKey($id)->whereHas('courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))->first();
        if (! $row) {
            throw ValidationException::withMessages(['faculty_allocation_id' => 'Select a Faculty Allocation belonging to this College.']);
        }

        return $row;
    }

    private function room(College $college, mixed $id): ?CollegeRoom
    {
        if (! $id) {
            return null;
        }$row = CollegeRoom::whereKey($id)->where('college_id', $college->id)->first();
        if (! $row) {
            throw ValidationException::withMessages(['room_id' => 'Select a Room belonging to this College.']);
        }

        return $row;
    }

    private function entry(College $college, int $id): TimetableEntry
    {
        $row = TimetableEntry::whereKey($id)->whereHas('facultyAllocation.courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))->first();
        if (! $row) {
            throw ValidationException::withMessages(['timetable_entry_id' => 'Select a Timetable entry belonging to this College.']);
        }

        return $row;
    }

    private function assertTimetableConflict(FacultyAllocation $allocation, ?CollegeRoom $room, int $day, string $start, string $end, string $from, ?string $until, ?int $ignore): void
    {
        $base = TimetableEntry::where('status', 'ACTIVE')->where('day_of_week', $day)->where('start_time', '<', $end)->where('end_time', '>', $start)->where('effective_from', '<=', $until ?: '9999-12-31')->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>=', $from))->when($ignore, fn ($q) => $q->whereKeyNot($ignore));
        $faculty = (clone $base)->whereHas('facultyAllocation', fn ($q) => $q->where('faculty_user_id', $allocation->faculty_user_id))->exists();
        if ($faculty) {
            throw ValidationException::withMessages(['start_time' => 'Faculty already has an overlapping active Timetable entry.']);
        } if ($room && (clone $base)->where('room_id', $room->id)->exists()) {
            throw ValidationException::withMessages(['room_id' => 'Room already has an overlapping active Timetable entry.']);
        } if ((clone $base)->whereHas('facultyAllocation', fn ($q) => $q->where('course_offering_id', $allocation->course_offering_id)->where(fn ($s) => $allocation->section_id ? $s->where('section_id', $allocation->section_id) : $s->whereNull('section_id')))->exists()) {
            throw ValidationException::withMessages(['start_time' => 'This Course Offering scope already has an overlapping active Timetable entry.']);
        }
    }

    private function assertWeeklyLoad(FacultyAllocation $allocation, TimetableEntry $candidate): void
    {
        if ($allocation->weekly_load === null) {
            return;
        }

        $candidateStart = Carbon::parse($candidate->start_time);
        $candidateMinutes = $candidateStart->diffInMinutes(Carbon::parse($candidate->end_time));
        $existingMinutes = TimetableEntry::query()
            ->where('faculty_allocation_id', $allocation->id)
            ->where('status', 'ACTIVE')
            ->whereKeyNot($candidate->id)
            ->where('effective_from', '<=', $candidate->effective_until ?: '9999-12-31')
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhere('effective_until', '>=', $candidate->effective_from))
            ->get(['start_time', 'end_time'])
            ->sum(fn (TimetableEntry $entry) => Carbon::parse($entry->start_time)->diffInMinutes(Carbon::parse($entry->end_time)));

        $allowedMinutes = (int) round((float) $allocation->weekly_load * 60);
        if ($existingMinutes + $candidateMinutes > $allowedMinutes) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Activation would use %.2f hours/week, exceeding this Faculty Allocation load of %.2f hours/week.',
                    ($existingMinutes + $candidateMinutes) / 60,
                    (float) $allocation->weekly_load,
                ),
            ]);
        }
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function audit(string $event, Model $row, College $college, int $actor, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert(['actor_user_id' => $actor, 'event' => $event, 'resource_type' => class_basename($row), 'resource_id' => $row->getKey(), 'scope_type' => 'COLLEGE', 'scope_reference' => 'college:'.$college->id, 'before' => $before ? json_encode($before) : null, 'after' => $after ? json_encode($after) : null, 'created_at' => now()]);
    }
}
