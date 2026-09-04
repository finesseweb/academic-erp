<?php

namespace App\Services;

use App\Models\AcademicCalendar;
use App\Models\AcademicCalendarEvent;
use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AcademicCalendarService
{
    public function create(array $data, int $universityId, int $actorId, ?string $ip): AcademicCalendar
    {
        $session = AcademicSession::query()
            ->whereKey($data['academic_session_id'])
            ->where('university_id', $universityId)
            ->firstOrFail();

        if (AcademicCalendar::query()->where('university_id', $universityId)->where('academic_session_id', $session->id)->exists()) {
            throw ValidationException::withMessages([
                'academic_session_id' => 'An Academic Calendar already exists for this Academic Session.',
            ]);
        }

        return DB::transaction(function () use ($data, $universityId, $actorId, $ip) {
            $calendar = AcademicCalendar::create([
                ...$data,
                'university_id' => $universityId,
                'code' => strtoupper(trim($data['code'])),
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit('ACADEMIC_CALENDAR_CREATED', 'AcademicCalendar', $calendar->id, $actorId, $ip, null, $calendar->toArray());

            return $calendar;
        });
    }

    public function update(AcademicCalendar $calendar, array $data, int $actorId, ?string $ip): AcademicCalendar
    {
        return DB::transaction(function () use ($calendar, $data, $actorId, $ip) {
            $before = $calendar->toArray();
            $calendar->update([
                ...$data,
                'code' => strtoupper(trim($data['code'])),
                'updated_by' => $actorId,
            ]);
            $this->audit('ACADEMIC_CALENDAR_UPDATED', 'AcademicCalendar', $calendar->id, $actorId, $ip, $before, $calendar->fresh()->toArray());

            return $calendar;
        });
    }

    public function changeStatus(AcademicCalendar $calendar, string $status, int $actorId, ?string $ip): void
    {
        if ($status === 'INACTIVE' && Schema::hasTable('college_academic_calendars')
            && DB::table('college_academic_calendars')->where('university_academic_calendar_id', $calendar->id)->where('status', 'ACTIVE')->exists()) {
            throw ValidationException::withMessages([
                'status' => 'This University Academic Calendar is used by an ACTIVE College Academic Calendar. Deactivate the dependent College Calendar first.',
            ]);
        }
        DB::transaction(function () use ($calendar, $status, $actorId, $ip) {
            $before = ['status' => $calendar->status];
            $calendar->update(['status' => $status, 'updated_by' => $actorId]);

            $auditEvent = $status === 'ACTIVE'
                ? 'ACADEMIC_CALENDAR_ACTIVATED'
                : 'ACADEMIC_CALENDAR_DEACTIVATED';

            $this->audit(
                $auditEvent,
                'AcademicCalendar',
                $calendar->id,
                $actorId,
                $ip,
                $before,
                ['status' => $status]
            );
        });
    }

    public function createEvent(AcademicCalendar $calendar, array $data, int $actorId, ?string $ip): AcademicCalendarEvent
    {
        $this->assertCalendarActive($calendar);
        $this->assertEventDatesInsideSession($calendar, $data['start_date'], $data['end_date']);

        return DB::transaction(function () use ($calendar, $data, $actorId, $ip) {
            $event = $calendar->events()->create([
                ...$data,
                'display_order' => $data['display_order'] ?? 0,
                'status' => 'ACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $this->audit('ACADEMIC_CALENDAR_EVENT_CREATED', 'AcademicCalendarEvent', $event->id, $actorId, $ip, null, $event->toArray());

            return $event;
        });
    }

    public function updateEvent(AcademicCalendar $calendar, AcademicCalendarEvent $event, array $data, int $actorId, ?string $ip): AcademicCalendarEvent
    {
        $this->assertCalendarActive($calendar);
        $this->assertEventOwned($calendar, $event);
        $this->assertEventDatesInsideSession($calendar, $data['start_date'], $data['end_date']);
        if (! (bool) ($data['allow_college_override'] ?? false) && Schema::hasTable('college_calendar_overrides')
            && DB::table('college_calendar_overrides')->where('academic_calendar_event_id', $event->id)->where('status', 'ACTIVE')->exists()) {
            throw ValidationException::withMessages([
                'allow_college_override' => 'Active College overrides exist for this event. Disable those College overrides before locking the University event.',
            ]);
        }

        return DB::transaction(function () use ($event, $data, $actorId, $ip) {
            $before = $event->toArray();
            $event->update([
                ...$data,
                'display_order' => $data['display_order'] ?? 0,
                'updated_by' => $actorId,
            ]);
            $this->audit('ACADEMIC_CALENDAR_EVENT_UPDATED', 'AcademicCalendarEvent', $event->id, $actorId, $ip, $before, $event->fresh()->toArray());

            return $event;
        });
    }

    public function changeEventStatus(AcademicCalendar $calendar, AcademicCalendarEvent $event, string $status, int $actorId, ?string $ip): void
    {
        $this->assertEventOwned($calendar, $event);
        if ($status === 'INACTIVE' && Schema::hasTable('college_calendar_overrides')
            && DB::table('college_calendar_overrides')->where('academic_calendar_event_id', $event->id)->where('status', 'ACTIVE')->exists()) {
            throw ValidationException::withMessages([
                'status' => 'This University event has an ACTIVE College override. Disable the College override first.',
            ]);
        }

        DB::transaction(function () use ($event, $status, $actorId, $ip) {
            $before = ['status' => $event->status];
            $event->update(['status' => $status, 'updated_by' => $actorId]);

            $auditEvent = $status === 'ACTIVE'
                ? 'ACADEMIC_CALENDAR_EVENT_ACTIVATED'
                : 'ACADEMIC_CALENDAR_EVENT_DEACTIVATED';

            $this->audit(
                $auditEvent,
                'AcademicCalendarEvent',
                $event->id,
                $actorId,
                $ip,
                $before,
                ['status' => $status]
            );
        });
    }


    private function assertCalendarActive(AcademicCalendar $calendar): void
    {
        if ($calendar->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'academic_calendar' => 'Activate the Academic Calendar before changing its events.',
            ]);
        }
    }

    private function assertEventDatesInsideSession(AcademicCalendar $calendar, string $startDate, string $endDate): void
    {
        $session = $calendar->academicSession()->firstOrFail();
        $start = date('Y-m-d', strtotime($startDate));
        $end = date('Y-m-d', strtotime($endDate));
        $sessionStart = $session->starts_on->format('Y-m-d');
        $sessionEnd = $session->ends_on->format('Y-m-d');

        if ($start < $sessionStart || $end > $sessionEnd) {
            throw ValidationException::withMessages([
                'start_date' => "Calendar events must fall within the Academic Session ({$sessionStart} to {$sessionEnd}).",
            ]);
        }
    }

    private function assertEventOwned(AcademicCalendar $calendar, AcademicCalendarEvent $event): void
    {
        abort_unless((int) $event->academic_calendar_id === (int) $calendar->id, 404);
    }

    private function audit(string $event, string $resourceType, int $resourceId, int $actorId, ?string $ip, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => $event,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'scope_type' => 'UNIVERSITY',
            'scope_reference' => 'university',
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
