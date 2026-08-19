<?php

namespace App\Services;

use App\Models\University;
use Illuminate\Support\Facades\DB;

class UniversityService
{
    public function update(University $university, array $data, int $actorId, ?string $ip): University
    {
        return DB::transaction(function () use ($university, $data, $actorId, $ip) {
            $before = $university->only(array_keys($data));
            $university->update($data);
            $after = $university->fresh()->only(array_keys($data));

            DB::table('audit_logs')->insert([
                'actor_user_id' => $actorId, 'event' => 'UNIVERSITY_UPDATED',
                'resource_type' => 'University', 'resource_id' => $university->id,
                'before' => json_encode($before), 'after' => json_encode($after),
                'ip_address' => $ip, 'created_at' => now(),
            ]);

            return $university->fresh();
        });
    }
}
