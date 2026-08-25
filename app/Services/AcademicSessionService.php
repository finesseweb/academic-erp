<?php

namespace App\Services;

use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicSessionService
{
    public function create(array $data, int $universityId, int $actorId, ?string $ip): AcademicSession
    {
        return DB::transaction(function () use ($data, $universityId, $actorId, $ip) {
            $session = AcademicSession::create([...$data, 'university_id' => $universityId, 'is_current' => false]);
            $this->audit('ACADEMIC_SESSION_CREATED', $session, $actorId, $ip, null, $session->toArray());

            return $session;
        });
    }

    public function update(AcademicSession $session, array $data, int $actorId, ?string $ip): AcademicSession
    {
        $this->assertCalendarEventsRemainInsideSession($session, $data['starts_on'], $data['ends_on']);

        return DB::transaction(function () use ($session, $data, $actorId, $ip) {
            $before = $session->toArray();
            $session->update($data);
            $this->audit(
                'ACADEMIC_SESSION_UPDATED',
                $session,
                $actorId,
                $ip,
                $before,
                $session->fresh()->toArray()
            );

            return $session;
        });
    }

    public function setCurrent(AcademicSession $session, int $actorId, ?string $ip): void
    {
        DB::transaction(function () use ($session, $actorId, $ip) {
            AcademicSession::where('university_id', $session->university_id)
                ->lockForUpdate()
                ->update(['is_current' => false]);

            $session->update(['is_current' => true, 'status' => 'ACTIVE']);

            $this->audit(
                'ACADEMIC_SESSION_CURRENT_CHANGED',
                $session,
                $actorId,
                $ip,
                null,
                ['is_current' => true]
            );
        });
    }

    public function changeStatus(AcademicSession $session, string $status, int $actorId, ?string $ip): void
    {
        DB::transaction(function () use ($session, $status, $actorId, $ip) {
            $before = [
                'status' => $session->status,
                'is_current' => $session->is_current,
            ];

            $session->update([
                'status' => $status,
                'is_current' => in_array($status, ['CLOSED', 'ARCHIVED'], true)
                    ? false
                    : $session->is_current,
            ]);

            $auditEvent = match ($status) {
                'ACTIVE' => 'ACADEMIC_SESSION_ACTIVATED',
                'CLOSED' => 'ACADEMIC_SESSION_CLOSED',
                'ARCHIVED' => 'ACADEMIC_SESSION_ARCHIVED',
                default => 'ACADEMIC_SESSION_STATUS_CHANGED',
            };

            $this->audit(
                $auditEvent,
                $session,
                $actorId,
                $ip,
                $before,
                [
                    'status' => $status,
                    'is_current' => $session->fresh()->is_current,
                ]
            );
        });
    }

    private function assertCalendarEventsRemainInsideSession(
        AcademicSession $session,
        string $newStartDate,
        string $newEndDate
    ): void {
        $newStart = date('Y-m-d', strtotime($newStartDate));
        $newEnd = date('Y-m-d', strtotime($newEndDate));

        $affected = DB::table('academic_calendar_events as ace')
            ->join(
                'academic_calendars as ac',
                'ac.id',
                '=',
                'ace.academic_calendar_id'
            )
            ->where('ac.academic_session_id', $session->id)
            ->where(function ($query) use ($newStart, $newEnd) {
                $query->whereDate('ace.start_date', '<', $newStart)
                    ->orWhereDate('ace.end_date', '>', $newEnd);
            })
            ->orderBy('ace.start_date')
            ->get([
                'ace.title',
                'ace.start_date',
                'ace.end_date',
            ]);

        if ($affected->isEmpty()) {
            return;
        }

        $preview = $affected
            ->take(5)
            ->map(fn ($event) => sprintf(
                '%s (%s to %s)',
                $event->title,
                $event->start_date,
                $event->end_date
            ))
            ->implode('; ');

        $extra = $affected->count() > 5
            ? ' Plus '.($affected->count() - 5).' more event(s).'
            : '';

        throw ValidationException::withMessages([
            'starts_on' =>
                "Academic Session dates cannot be changed because {$affected->count()} Academic Calendar event(s) would fall outside the new session range ({$newStart} to {$newEnd}). Update those Calendar events first. Affected: {$preview}.{$extra}",
        ]);
    }

    private function audit(
        string $event,
        AcademicSession $session,
        int $actorId,
        ?string $ip,
        ?array $before,
        ?array $after
    ): void {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => $event,
            'resource_type' => 'AcademicSession',
            'resource_id' => $session->id,
            'scope_type' => 'UNIVERSITY',
            'scope_reference' => 'university',
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
