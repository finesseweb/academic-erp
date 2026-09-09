<?php

namespace App\Services;

use App\Models\AcademicCalendar;
use App\Models\AcademicCalendarEvent;
use App\Models\College;
use App\Models\CollegeAcademicCalendar;
use App\Models\CollegeProgramOffering;
use App\Models\CollegeCalendarOverride;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CollegeAcademicCalendarService
{
    public function create(College $college, array $data, int $actorId, ?string $ip): CollegeAcademicCalendar
    {
        $this->assertCollegeActive($college);
        $parent = $this->validateUniversityCalendar($college, (int) $data['university_academic_calendar_id'], true);

        if (CollegeAcademicCalendar::query()->where('college_id', $college->id)->where('university_academic_calendar_id', $parent->id)->exists()) {
            throw ValidationException::withMessages([
                'university_academic_calendar_id' => 'This College already has an Academic Calendar for the selected Academic Session.',
            ]);
        }

        return DB::transaction(function () use ($college, $parent, $data, $actorId, $ip) {
            $calendar = CollegeAcademicCalendar::create([
                'college_id' => $college->id,
                'university_academic_calendar_id' => $parent->id,
                'status' => 'INACTIVE',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit('COLLEGE_ACADEMIC_CALENDAR_ADOPTED', 'CollegeAcademicCalendar', $calendar->id, $college, $actorId, $ip, null, $calendar->toArray());
            return $calendar;
        });
    }

    public function update(CollegeAcademicCalendar $calendar, College $college, array $data, int $actorId, ?string $ip): void
    {
        $this->assertOwned($calendar, $college);
        $this->assertCollegeActive($college);

        DB::transaction(function () use ($calendar, $college, $data, $actorId, $ip) {
            $before = $calendar->toArray();
            $calendar->update(['notes' => $data['notes'] ?? null, 'updated_by' => $actorId]);
            $this->audit('COLLEGE_ACADEMIC_CALENDAR_UPDATED', 'CollegeAcademicCalendar', $calendar->id, $college, $actorId, $ip, $before, $calendar->fresh()->toArray());
        });
    }

    public function changeStatus(CollegeAcademicCalendar $calendar, College $college, string $status, int $actorId, ?string $ip): void
    {
        $this->assertOwned($calendar, $college);
        $this->assertCollegeActive($college);
        if ($calendar->status === $status) return;

        if ($status === 'ACTIVE') {
            $this->validateUniversityCalendar($college, (int) $calendar->university_academic_calendar_id, true);
        }

        DB::transaction(function () use ($calendar, $college, $status, $actorId, $ip) {
            $before = ['status' => $calendar->status];
            $calendar->update(['status' => $status, 'updated_by' => $actorId]);
            $this->audit(
                $status === 'ACTIVE' ? 'COLLEGE_ACADEMIC_CALENDAR_ACTIVATED' : 'COLLEGE_ACADEMIC_CALENDAR_DEACTIVATED',
                'CollegeAcademicCalendar', $calendar->id, $college, $actorId, $ip, $before, ['status' => $status]
            );
        });
    }

    public function createOverride(CollegeAcademicCalendar $calendar, College $college, array $data, int $actorId, ?string $ip): CollegeCalendarOverride
    {
        $this->assertOwned($calendar, $college);
        $this->assertCollegeActive($college);
        $event = $this->validateOverrideEvent($calendar, (int) $data['academic_calendar_event_id']);
        $this->assertDatesInsideSession($calendar, $data['start_date'], $data['end_date']);
        $this->assertOverrideInsideTermPeriod($event, $data['start_date'], $data['end_date']);

        $existing = CollegeCalendarOverride::query()
            ->where('college_academic_calendar_id', $calendar->id)
            ->where('academic_calendar_event_id', $event->id)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'academic_calendar_event_id' => 'This University event already has a College override. Edit or re-enable the existing override.',
            ]);
        }

        return DB::transaction(function () use ($calendar, $college, $event, $data, $actorId, $ip) {
            $override = CollegeCalendarOverride::create([
                'college_academic_calendar_id' => $calendar->id,
                'academic_calendar_id' => $calendar->university_academic_calendar_id,
                'academic_calendar_event_id' => $event->id,
                'title' => trim($data['title']),
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'description' => $data['description'] ?? null,
                'reason' => trim($data['reason']),
                'status' => 'ACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $this->audit('COLLEGE_CALENDAR_OVERRIDE_CREATED', 'CollegeCalendarOverride', $override->id, $college, $actorId, $ip, null, $override->toArray());
            return $override;
        });
    }

    public function updateOverride(CollegeAcademicCalendar $calendar, CollegeCalendarOverride $override, College $college, array $data, int $actorId, ?string $ip): void
    {
        $this->assertOwned($calendar, $college);
        $this->assertOverrideOwned($calendar, $override);
        $this->assertCollegeActive($college);
        $this->validateOverrideEvent($calendar, (int) $override->academic_calendar_event_id);
        $this->assertDatesInsideSession($calendar, $data['start_date'], $data['end_date']);
        $this->assertOverrideInsideTermPeriod($override->universityEvent()->firstOrFail(), $data['start_date'], $data['end_date']);

        DB::transaction(function () use ($override, $college, $data, $actorId, $ip) {
            $before = $override->toArray();
            $override->update([
                'title' => trim($data['title']),
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'description' => $data['description'] ?? null,
                'reason' => trim($data['reason']),
                'updated_by' => $actorId,
            ]);
            $this->audit('COLLEGE_CALENDAR_OVERRIDE_UPDATED', 'CollegeCalendarOverride', $override->id, $college, $actorId, $ip, $before, $override->fresh()->toArray());
        });
    }

    public function changeOverrideStatus(CollegeAcademicCalendar $calendar, CollegeCalendarOverride $override, College $college, string $status, int $actorId, ?string $ip): void
    {
        $this->assertOwned($calendar, $college);
        $this->assertOverrideOwned($calendar, $override);
        $this->assertCollegeActive($college);
        if ($status === 'ACTIVE') {
            $this->validateOverrideEvent($calendar, (int) $override->academic_calendar_event_id);
            $this->assertDatesInsideSession($calendar, $override->start_date->format('Y-m-d'), $override->end_date->format('Y-m-d'));
        }
        if ($override->status === $status) return;

        DB::transaction(function () use ($override, $college, $status, $actorId, $ip) {
            $before = ['status' => $override->status];
            $override->update(['status' => $status, 'updated_by' => $actorId]);
            $this->audit(
                $status === 'ACTIVE' ? 'COLLEGE_CALENDAR_OVERRIDE_ACTIVATED' : 'COLLEGE_CALENDAR_OVERRIDE_DEACTIVATED',
                'CollegeCalendarOverride', $override->id, $college, $actorId, $ip, $before, ['status' => $status]
            );
        });
    }

    private function validateUniversityCalendar(College $college, int $id, bool $requireActive): AcademicCalendar
    {
        $calendar = AcademicCalendar::query()->with('academicSession')->whereKey($id)->where('university_id', $college->university_id)->first();
        if (! $calendar) throw ValidationException::withMessages(['university_academic_calendar_id' => 'Select a University Academic Calendar belonging to this College University.']);
        if ($requireActive && $calendar->status !== 'ACTIVE') throw ValidationException::withMessages(['university_academic_calendar_id' => 'The University Academic Calendar must be ACTIVE.']);
        $sessionUsedByCollege = CollegeProgramOffering::query()
            ->where('college_id', $college->id)
            ->where('academic_session_id', $calendar->academic_session_id)
            ->exists();
        if (! $sessionUsedByCollege) {
            throw ValidationException::withMessages(['university_academic_calendar_id' => 'This College has no Program Offering in the selected Academic Session. Create the College academic structure for that Session first.']);
        }
        return $calendar;
    }

    private function validateOverrideEvent(CollegeAcademicCalendar $calendar, int $eventId): AcademicCalendarEvent
    {
        $event = AcademicCalendarEvent::query()->whereKey($eventId)->where('academic_calendar_id', $calendar->university_academic_calendar_id)->first();
        if (! $event) throw ValidationException::withMessages(['academic_calendar_event_id' => 'Select an event from the adopted University Academic Calendar.']);
        if ($event->status !== 'ACTIVE') throw ValidationException::withMessages(['academic_calendar_event_id' => 'Only ACTIVE University calendar events can be overridden.']);
        if (! $event->allow_college_override) throw ValidationException::withMessages(['academic_calendar_event_id' => 'University governance does not allow College override for this event.']);
        return $event;
    }


    private function assertOverrideInsideTermPeriod(AcademicCalendarEvent $event, string $startDate, string $endDate): void
    {
        if (!$event->academic_calendar_term_period_id) return;
        $period=$event->termPeriod()->first();
        if(!$period) return;
        $start=date('Y-m-d',strtotime($startDate)); $end=date('Y-m-d',strtotime($endDate));
        if($start<$period->start_date->format('Y-m-d') || $end>$period->end_date->format('Y-m-d')) {
            throw ValidationException::withMessages(['start_date'=>'College event override must remain inside the University Curriculum Academic Period.']);
        }
    }

    private function assertDatesInsideSession(CollegeAcademicCalendar $calendar, string $startDate, string $endDate): void
    {
        $parent = AcademicCalendar::query()->with('academicSession')->findOrFail($calendar->university_academic_calendar_id);
        $session = $parent->academicSession;
        $start = date('Y-m-d', strtotime($startDate));
        $end = date('Y-m-d', strtotime($endDate));
        $sessionStart = $session->starts_on->format('Y-m-d');
        $sessionEnd = $session->ends_on->format('Y-m-d');
        if ($start < $sessionStart || $end > $sessionEnd) {
            throw ValidationException::withMessages(['start_date' => "College override dates must remain within the Academic Session ({$sessionStart} to {$sessionEnd})."]);
        }
    }

    private function assertCollegeActive(College $college): void
    {
        if ($college->status !== 'ACTIVE') throw ValidationException::withMessages(['college' => 'College Academic Calendar cannot be changed while this College is inactive.']);
    }

    private function assertOwned(CollegeAcademicCalendar $calendar, College $college): void
    {
        abort_unless((int) $calendar->college_id === (int) $college->id, 404);
    }

    private function assertOverrideOwned(CollegeAcademicCalendar $calendar, CollegeCalendarOverride $override): void
    {
        abort_unless((int) $override->college_academic_calendar_id === (int) $calendar->id, 404);
    }

    private function audit(string $event, string $resourceType, int $resourceId, College $college, int $actorId, ?string $ip, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => $event,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'scope_type' => 'COLLEGE',
            'scope_reference' => 'college:'.$college->id,
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
