<?php

namespace App\Services;

use App\Models\DegreeLevel;
use Illuminate\Support\Facades\DB;

class DegreeLevelService
{
    public function create(array $data, int $universityId, int $actorId, ?string $ip): DegreeLevel
    {
        return DB::transaction(function () use ($data, $universityId, $actorId, $ip) {
            $level = DegreeLevel::create([...$data, 'university_id' => $universityId]);
            $this->audit('DEGREE_LEVEL_CREATED', $level, $actorId, $ip, null, $level->toArray());

            return $level;
        });
    }

    public function update(DegreeLevel $level, array $data, int $actorId, ?string $ip): void
    {
        DB::transaction(function () use ($level, $data, $actorId, $ip) {
            $before = $level->toArray();
            $level->update($data);
            $this->audit('DEGREE_LEVEL_UPDATED', $level, $actorId, $ip, $before, $level->fresh()->toArray());
        });
    }

    public function status(DegreeLevel $level, string $status, int $actorId, ?string $ip): void
    {
        DB::transaction(function () use ($level, $status, $actorId, $ip) {
            $before = ['status' => $level->status];
            $level->update(['status' => $status]);
            $this->audit('DEGREE_LEVEL_STATUS_CHANGED', $level, $actorId, $ip, $before, ['status' => $status]);
        });
    }

    private function audit(string $event, DegreeLevel $level, int $actorId, ?string $ip, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert(['actor_user_id' => $actorId, 'event' => $event, 'resource_type' => 'DegreeLevel', 'resource_id' => $level->id, 'scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'before' => $before ? json_encode($before) : null, 'after' => $after ? json_encode($after) : null, 'ip_address' => $ip, 'created_at' => now()]);
    }
}
