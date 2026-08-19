<?php

namespace App\Services;

use App\Models\AuthorizedSignatory;
use App\Models\University;
use Illuminate\Support\Facades\DB;

class AuthorizedSignatoryService
{
    public function create(University $university, array $data, int $actorId, ?string $ip): AuthorizedSignatory
    {
        return DB::transaction(function () use ($university, $data, $actorId, $ip) {
            $signatory = $university->authorizedSignatories()->create($data);
            $this->audit('AUTHORIZED_SIGNATORY_CREATED', $signatory, $actorId, $ip, null, $signatory->toArray());

            return $signatory;
        });
    }

    public function update(AuthorizedSignatory $signatory, array $data, int $actorId, ?string $ip): AuthorizedSignatory
    {
        return DB::transaction(function () use ($signatory, $data, $actorId, $ip) {
            $before = $signatory->only(array_keys($data));
            $signatory->update($data);
            $after = $signatory->fresh()->only(array_keys($data));
            $this->audit('AUTHORIZED_SIGNATORY_UPDATED', $signatory, $actorId, $ip, $before, $after);

            return $signatory->fresh();
        });
    }

    public function changeStatus(AuthorizedSignatory $signatory, string $status, int $actorId, ?string $ip): AuthorizedSignatory
    {
        return DB::transaction(function () use ($signatory, $status, $actorId, $ip) {
            $before = ['status' => $signatory->status];
            $signatory->update(['status' => $status]);
            $this->audit('AUTHORIZED_SIGNATORY_STATUS_CHANGED', $signatory, $actorId, $ip, $before, ['status' => $status]);

            return $signatory->fresh();
        });
    }

    private function audit(string $event, AuthorizedSignatory $signatory, int $actorId, ?string $ip, ?array $before, array $after): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId, 'event' => $event, 'resource_type' => 'AuthorizedSignatory',
            'resource_id' => $signatory->id, 'before' => $before ? json_encode($before) : null,
            'after' => json_encode($after), 'ip_address' => $ip, 'created_at' => now(),
        ]);
    }
}
