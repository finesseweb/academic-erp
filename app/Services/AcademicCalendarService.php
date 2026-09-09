<?php

namespace App\Services;

use App\Models\AcademicCalendar;
use App\Models\AcademicCalendarEvent;
use App\Models\AcademicCalendarTermPeriod;
use App\Models\CurriculumTerm;
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
        $this->assertEventInsideTermPeriod($calendar, $data['academic_calendar_term_period_id'] ?? null, $data['start_date'], $data['end_date']);

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
        $this->assertEventInsideTermPeriod($calendar, $data['academic_calendar_term_period_id'] ?? null, $data['start_date'], $data['end_date']);
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



    public function upsertTermPeriod(AcademicCalendar $calendar, ?AcademicCalendarTermPeriod $period, array $data, int $actorId, ?string $ip): AcademicCalendarTermPeriod
    {
        $this->assertCalendarActive($calendar);
        $this->assertEventDatesInsideSession($calendar, $data['start_date'], $data['end_date']);
        if ($period) abort_unless((int)$period->academic_calendar_id === (int)$calendar->id, 404);
        $termId=(int)($period?->curriculum_term_id ?? $data['curriculum_term_id']);
        $term=CurriculumTerm::query()->with(['curriculum.academicSession'])->findOrFail($termId);
        $curriculum=$term->curriculum;
        if ((int)$curriculum->university_id !== (int)$calendar->university_id || (int)$curriculum->academic_session_id !== (int)$calendar->academic_session_id || $term->status !== 'ACTIVE' || ! $curriculum->isCurrentApprovedVersion()) {
            throw ValidationException::withMessages(['curriculum_term_id'=>'Select an ACTIVE term from the current APPROVED Curriculum for this University Academic Session.']);
        }
        $this->assertTermPeriodInsideCurriculumEffectiveWindow($calendar, $curriculum, $data['start_date'], $data['end_date']);
        $this->assertTermPeriodDoesNotOverlapCurriculumPeriod($calendar, $curriculum, $period, $data['start_date'], $data['end_date']);
        if (!$period && AcademicCalendarTermPeriod::where('academic_calendar_id',$calendar->id)->where('curriculum_term_id',$termId)->exists()) {
            throw ValidationException::withMessages(['curriculum_term_id'=>'This Curriculum Term already has an Academic Period in this calendar.']);
        }
        return DB::transaction(function() use($calendar,$period,$data,$termId,$actorId,$ip){
            $before=$period?->toArray();
            $values=['academic_calendar_id'=>$calendar->id,'curriculum_term_id'=>$termId,'start_date'=>$data['start_date'],'end_date'=>$data['end_date'],'allow_college_override'=>(bool)$data['allow_college_override'],'status'=>'ACTIVE','updated_by'=>$actorId];
            if($period)$period->update($values); else $period=AcademicCalendarTermPeriod::create($values+['created_by'=>$actorId]);
            $this->audit($before?'ACADEMIC_CALENDAR_TERM_PERIOD_UPDATED':'ACADEMIC_CALENDAR_TERM_PERIOD_CREATED','AcademicCalendarTermPeriod',$period->id,$actorId,$ip,$before,$period->fresh()->toArray());
            return $period;
        });
    }


    private function assertTermPeriodInsideCurriculumEffectiveWindow(AcademicCalendar $calendar, \App\Models\Curriculum $curriculum, string $startDate, string $endDate): void
    {
        $session = $calendar->academicSession()->firstOrFail();
        $start = date('Y-m-d', strtotime($startDate));
        $end = date('Y-m-d', strtotime($endDate));
        $sessionStart = $session->starts_on->format('Y-m-d');
        $sessionEnd = $session->ends_on->format('Y-m-d');
        $effectiveStart = $curriculum->effective_from?->format('Y-m-d') ?: $sessionStart;
        $effectiveEnd = $curriculum->effective_to?->format('Y-m-d') ?: $sessionEnd;
        $allowedStart = max($sessionStart, $effectiveStart);
        $allowedEnd = min($sessionEnd, $effectiveEnd);

        if ($allowedStart > $allowedEnd) {
            throw ValidationException::withMessages([
                'start_date' => 'The Curriculum Effective From/To window does not overlap its Academic Session. Correct the Curriculum validity before assigning Academic Period dates.',
            ]);
        }

        if ($start < $allowedStart || $end > $allowedEnd) {
            throw ValidationException::withMessages([
                'start_date' => "Academic Period dates must remain inside the Curriculum effective window ({$allowedStart} to {$allowedEnd}).",
            ]);
        }
    }

    private function assertTermPeriodDoesNotOverlapCurriculumPeriod(
        AcademicCalendar $calendar,
        \App\Models\Curriculum $curriculum,
        ?AcademicCalendarTermPeriod $editingPeriod,
        string $startDate,
        string $endDate
    ): void {
        $start = date('Y-m-d', strtotime($startDate));
        $end = date('Y-m-d', strtotime($endDate));

        $overlap = AcademicCalendarTermPeriod::query()
            ->with('curriculumTerm')
            ->where('academic_calendar_id', $calendar->id)
            ->where('status', 'ACTIVE')
            ->when($editingPeriod, fn ($query) => $query->where('id', '!=', $editingPeriod->id))
            ->whereHas('curriculumTerm', fn ($query) => $query->where('curriculum_id', $curriculum->id))
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->first();

        if ($overlap) {
            $name = $overlap->curriculumTerm?->name ?: 'another Academic Period';
            $occupiedStart = $overlap->start_date->format('Y-m-d');
            $occupiedEnd = $overlap->end_date->format('Y-m-d');

            throw ValidationException::withMessages([
                'start_date' => "Selected dates overlap {$name} ({$occupiedStart} to {$occupiedEnd}) in the same Curriculum. Academic Periods of one Curriculum cannot share dates.",
            ]);
        }
    }


    private function assertEventInsideTermPeriod(AcademicCalendar $calendar, ?int $periodId, string $startDate, string $endDate): void
    {
        if (!$periodId) return; // University-wide holidays/events may remain unscoped.
        $period=AcademicCalendarTermPeriod::query()->whereKey($periodId)->where('academic_calendar_id',$calendar->id)->where('status','ACTIVE')->first();
        if(!$period) throw ValidationException::withMessages(['academic_calendar_term_period_id'=>'Select an ACTIVE Academic Period from this calendar.']);
        $start=date('Y-m-d',strtotime($startDate)); $end=date('Y-m-d',strtotime($endDate));
        if($start<$period->start_date->format('Y-m-d') || $end>$period->end_date->format('Y-m-d')) {
            throw ValidationException::withMessages(['start_date'=>'The event dates must remain inside the selected Curriculum Term Academic Period.']);
        }
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
