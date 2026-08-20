<?php

namespace App\Services;

use App\Models\AcademicSession;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($session, $data, $actorId, $ip) {
            $before = $session->toArray();
            $session->update($data);
            $this->audit('ACADEMIC_SESSION_UPDATED', $session, $actorId, $ip, $before, $session->fresh()->toArray());

            return $session;
        });
    }

    public function setCurrent(AcademicSession $session, int $actorId, ?string $ip): void
    {
        DB::transaction(function () use ($session, $actorId, $ip) {
            AcademicSession::where('university_id', $session->university_id)->lockForUpdate()->update(['is_current' => false]);
            $session->update(['is_current' => true, 'status' => 'ACTIVE']);
            $this->audit('ACADEMIC_SESSION_CURRENT_CHANGED', $session, $actorId, $ip, null, ['is_current' => true]);
        });
    }

    public function changeStatus(AcademicSession $session, string $status, int $actorId, ?string $ip): void
    {
        DB::transaction(function () use ($session, $status, $actorId, $ip) {
            $before = ['status' => $session->status, 'is_current' => $session->is_current];
            $session->update(['status' => $status, 'is_current' => in_array($status, ['CLOSED', 'ARCHIVED']) ? false : $session->is_current]);
            $this->audit('ACADEMIC_SESSION_STATUS_CHANGED', $session, $actorId, $ip, $before, ['status' => $status, 'is_current' => $session->fresh()->is_current]);
        });
    }

    private function audit(string $event, AcademicSession $session, int $actorId, ?string $ip, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert(['actor_user_id' => $actorId, 'event' => $event, 'resource_type' => 'AcademicSession', 'resource_id' => $session->id, 'scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'before' => $before ? json_encode($before) : null, 'after' => $after ? json_encode($after) : null, 'ip_address' => $ip, 'created_at' => now()]);
    }
}
