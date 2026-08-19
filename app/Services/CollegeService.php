<?php

namespace App\Services;

use App\Models\College;
use App\Models\University;
use Illuminate\Support\Facades\DB;

class CollegeService
{
    public function create(University $university, array $data, int $actorId, ?string $ip): College
    {
        return DB::transaction(function () use ($university, $data, $actorId, $ip) {
            $college = $university->colleges()->create($data);
            $this->audit('COLLEGE_CREATED', $college, $actorId, $ip, null, $college->toArray());

            return $college;
        });
    }

    public function update(College $college, array $data, int $actorId, ?string $ip): College
    {
        return DB::transaction(function () use ($college, $data, $actorId, $ip) {
            $before = $college->only(array_keys($data));
            $college->update($data);
            $after = $college->fresh()->only(array_keys($data));
            $this->audit('COLLEGE_UPDATED', $college, $actorId, $ip, $before, $after);

            return $college->fresh();
        });
    }

    public function changeStatus(College $college, string $status, int $actorId, ?string $ip): College
    {
        return DB::transaction(function () use ($college, $status, $actorId, $ip) {
            $before = ['status' => $college->status];
            $college->update(['status' => $status]);
            $this->audit('COLLEGE_STATUS_CHANGED', $college, $actorId, $ip, $before, ['status' => $status]);

            return $college->fresh();
        });
    }

    private function audit(string $event, College $college, int $actorId, ?string $ip, ?array $before, array $after): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId, 'event' => $event, 'resource_type' => 'College',
            'resource_id' => $college->id, 'before' => $before ? json_encode($before) : null,
            'after' => json_encode($after), 'ip_address' => $ip, 'created_at' => now(),
        ]);
    }
}
