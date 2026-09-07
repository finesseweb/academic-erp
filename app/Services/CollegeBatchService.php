<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CollegeBatchService
{
    public function create(
        College $college,
        array $data,
        int $actorId,
        ?string $ip
    ): Batch {
        $this->assertCollegeActive($college);
        $offering = $this->validateOffering($college, (int) $data['college_program_offering_id']);
        $this->assertUnique($offering, $data['code']);

        return DB::transaction(function () use ($data, $offering, $college, $actorId, $ip) {
            $batch = Batch::create([
                'college_program_offering_id' => $offering->id,
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'notes' => $data['notes'] ?? null,
                'status' => 'INACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit(
                'COLLEGE_BATCH_CREATED',
                $batch,
                $college,
                $actorId,
                $ip,
                null,
                $batch->toArray()
            );

            return $batch;
        });
    }

    public function update(
        Batch $batch,
        College $college,
        array $data,
        int $actorId,
        ?string $ip
    ): Batch {
        $this->assertOwned($batch, $college);
        $this->assertCollegeActive($college);

        $offering = $this->validateOffering(
            $college,
            (int) $data['college_program_offering_id'],
            false
        );

        if (
            (int) $batch->college_program_offering_id !== (int) $offering->id
            && ($batch->status === 'ACTIVE' || $this->hasDownstreamReferences($batch->id))
        ) {
            throw ValidationException::withMessages([
                'college_program_offering_id' =>
                    'The Program Offering cannot be changed after this Batch is active or has downstream records.',
            ]);
        }

        $this->assertUnique($offering, $data['code'], $batch->id);

        return DB::transaction(function () use ($batch, $data, $offering, $college, $actorId, $ip) {
            $before = $batch->toArray();

            $batch->update([
                'college_program_offering_id' => $offering->id,
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'notes' => $data['notes'] ?? null,
                'updated_by' => $actorId,
            ]);

            $this->audit(
                'COLLEGE_BATCH_UPDATED',
                $batch,
                $college,
                $actorId,
                $ip,
                $before,
                $batch->fresh()->toArray()
            );

            return $batch;
        });
    }

    public function changeStatus(
        Batch $batch,
        College $college,
        string $status,
        int $actorId,
        ?string $ip
    ): void {
        $this->assertOwned($batch, $college);
        $this->assertCollegeActive($college);

        if ($batch->status === $status) {
            return;
        }

        if ($status === 'ACTIVE') {
            $this->validateOffering(
                $college,
                (int) $batch->college_program_offering_id,
                true
            );
        }

        if ($status === 'INACTIVE' && $this->hasActiveDownstreamReferences($batch->id)) {
            throw ValidationException::withMessages([
                'status' =>
                    'This Batch has active Sections or Student Enrollment records. Disable or move those downstream records first.',
            ]);
        }

        DB::transaction(function () use ($batch, $college, $status, $actorId, $ip) {
            $before = ['status' => $batch->status];
            $batch->update(['status' => $status, 'updated_by' => $actorId]);

            $this->audit(
                $status === 'ACTIVE'
                    ? 'COLLEGE_BATCH_ACTIVATED'
                    : 'COLLEGE_BATCH_DEACTIVATED',
                $batch,
                $college,
                $actorId,
                $ip,
                $before,
                ['status' => $status]
            );
        });
    }

    private function validateOffering(
        College $college,
        int $offeringId,
        bool $requireActive = true
    ): CollegeProgramOffering {
        $query = CollegeProgramOffering::query()
            ->whereKey($offeringId)
            ->where('college_id', $college->id);

        if ($requireActive) {
            $query->where('status', 'ACTIVE');
        }

        $offering = $query->first();

        if (! $offering) {
            throw ValidationException::withMessages([
                'college_program_offering_id' => $requireActive
                    ? 'Select an ACTIVE Program Offering belonging to this College.'
                    : 'Select a Program Offering belonging to this College.',
            ]);
        }

        if ($requireActive) {
            $intake = DB::table('college_program_intakes')
                ->where('college_program_offering_id', $offering->id)
                ->where('status', 'ACTIVE')
                ->first();

            if (! $intake) {
                throw ValidationException::withMessages([
                    'college_program_offering_id' =>
                        'This Program Offering must have an ACTIVE Intake / Seat Capacity before its Batch can be activated.',
                ]);
            }
        }

        return $offering;
    }

    private function assertUnique(
        CollegeProgramOffering $offering,
        string $code,
        ?int $ignoreId = null
    ): void {
        $query = Batch::query()
            ->where('college_program_offering_id', $offering->id)
            ->whereRaw('LOWER(code) = ?', [strtolower(trim($code))]);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'code' => 'This Program Offering already has a Batch with the same code.',
            ]);
        }
    }

    private function assertCollegeActive(College $college): void
    {
        if ($college->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'college' => 'Batch Management cannot be changed while this College is inactive.',
            ]);
        }
    }

    private function assertOwned(Batch $batch, College $college): void
    {
        $owned = CollegeProgramOffering::query()
            ->whereKey($batch->college_program_offering_id)
            ->where('college_id', $college->id)
            ->exists();

        abort_unless($owned, 404);
    }

    private function hasDownstreamReferences(int $batchId): bool
    {
        foreach ([
            ['sections', 'batch_id'],
            ['student_enrollments', 'batch_id'],
        ] as [$table, $column]) {
            if (
                Schema::hasTable($table)
                && Schema::hasColumn($table, $column)
                && DB::table($table)->where($column, $batchId)->exists()
            ) {
                return true;
            }
        }

        return false;
    }

    private function hasActiveDownstreamReferences(int $batchId): bool
    {
        foreach ([
            ['sections', 'batch_id'],
            ['student_enrollments', 'batch_id'],
        ] as [$table, $column]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $query = DB::table($table)->where($column, $batchId);

            if (Schema::hasColumn($table, 'status')) {
                $query->whereIn('status', ['ACTIVE', 'ENROLLED']);
            }

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }

    private function audit(
        string $event,
        Batch $batch,
        College $college,
        int $actorId,
        ?string $ip,
        ?array $before,
        ?array $after
    ): void {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => $event,
            'resource_type' => 'Batch',
            'resource_id' => $batch->id,
            'scope_type' => 'COLLEGE',
            'scope_reference' => 'college:'.$college->id,
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
