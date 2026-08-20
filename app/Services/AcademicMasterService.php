<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AcademicMasterService
{
    public function create(string $model, array $data, int $universityId, int $actorId, ?string $ip, string $resource, string $event): Model
    {
        return DB::transaction(function () use ($model, $data, $universityId, $actorId, $ip, $resource, $event) {
            $record = $model::create([...$data, 'university_id' => $universityId]);
            $this->audit($event.'_CREATED', $resource, $record, $actorId, $ip, null, $record->toArray());

            return $record;
        });
    }

    public function update(Model $record, array $data, int $actorId, ?string $ip, string $resource, string $event): void
    {
        DB::transaction(function () use ($record, $data, $actorId, $ip, $resource, $event) {
            $before = $record->toArray();
            $record->update($data);
            $this->audit($event.'_UPDATED', $resource, $record, $actorId, $ip, $before, $record->fresh()->toArray());
        });
    }

    public function status(Model $record, string $status, int $actorId, ?string $ip, string $resource, string $event): void
    {
        DB::transaction(function () use ($record, $status, $actorId, $ip, $resource, $event) {
            $before = ['status' => $record->getAttribute('status')];
            $record->update(['status' => $status]);
            $this->audit($event.'_STATUS_CHANGED', $resource, $record, $actorId, $ip, $before, ['status' => $status]);
        });
    }

    private function audit(string $event, string $resource, Model $record, int $actorId, ?string $ip, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert(['actor_user_id' => $actorId, 'event' => $event, 'resource_type' => $resource, 'resource_id' => $record->getKey(), 'scope_type' => 'UNIVERSITY', 'scope_reference' => 'university', 'before' => $before ? json_encode($before) : null, 'after' => $after ? json_encode($after) : null, 'ip_address' => $ip, 'created_at' => now()]);
    }
}
