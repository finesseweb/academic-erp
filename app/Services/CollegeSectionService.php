<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\College;
use App\Models\Section;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CollegeSectionService
{
    public function create(College $college, array $data, int $actorId, ?string $ip): Section
    {
        $this->assertCollegeActive($college);
        $batch = $this->validateBatch($college, (int) $data['batch_id'], true);
        $this->assertUnique($batch, $data['code']);

        return DB::transaction(function () use ($college, $batch, $data, $actorId, $ip) {
            $section = Section::create([
                'batch_id' => $batch->id,
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'status' => 'INACTIVE',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit('COLLEGE_SECTION_CREATED', $section, $college, $actorId, $ip, null, $section->toArray());

            return $section;
        });
    }

    public function update(Section $section, College $college, array $data, int $actorId, ?string $ip): Section
    {
        $this->assertOwned($section, $college);
        $this->assertCollegeActive($college);
        $batch = $this->validateBatch($college, (int) $data['batch_id'], false);

        if (
            (int) $section->batch_id !== (int) $batch->id
            && ($section->status === 'ACTIVE' || $this->hasDownstreamReferences($section->id))
        ) {
            throw ValidationException::withMessages([
                'batch_id' => 'The parent Batch cannot be changed after this Section is active or has Student Enrollment records.',
            ]);
        }

        $this->assertUnique($batch, $data['code'], $section->id);

        return DB::transaction(function () use ($section, $college, $batch, $data, $actorId, $ip) {
            $before = $section->toArray();
            $section->update([
                'batch_id' => $batch->id,
                'code' => strtoupper(trim($data['code'])),
                'name' => trim($data['name']),
                'notes' => $data['notes'] ?? null,
                'updated_by' => $actorId,
            ]);

            $this->audit('COLLEGE_SECTION_UPDATED', $section, $college, $actorId, $ip, $before, $section->fresh()->toArray());

            return $section;
        });
    }

    public function changeStatus(Section $section, College $college, string $status, int $actorId, ?string $ip): void
    {
        $this->assertOwned($section, $college);
        $this->assertCollegeActive($college);

        if ($section->status === $status) {
            return;
        }

        if ($status === 'ACTIVE') {
            $this->validateBatch($college, (int) $section->batch_id, true);
        }

        if ($status === 'INACTIVE' && $this->hasActiveDownstreamReferences($section->id)) {
            throw ValidationException::withMessages([
                'status' => 'This Section has active Student Enrollment records. Move or close those enrollments first.',
            ]);
        }

        DB::transaction(function () use ($section, $college, $status, $actorId, $ip) {
            $before = ['status' => $section->status];
            $section->update(['status' => $status, 'updated_by' => $actorId]);

            $this->audit(
                $status === 'ACTIVE' ? 'COLLEGE_SECTION_ACTIVATED' : 'COLLEGE_SECTION_DEACTIVATED',
                $section,
                $college,
                $actorId,
                $ip,
                $before,
                ['status' => $status]
            );
        });
    }

    private function validateBatch(College $college, int $batchId, bool $requireActive): Batch
    {
        $batch = Batch::query()
            ->with(['offering.intake'])
            ->whereKey($batchId)
            ->whereHas('offering', fn ($q) => $q->where('college_id', $college->id))
            ->first();

        if (! $batch) {
            throw ValidationException::withMessages(['batch_id' => 'Select a Batch belonging to this College.']);
        }

        if ($requireActive) {
            if ($batch->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['batch_id' => 'Select an ACTIVE Batch.']);
            }

            if ($batch->offering?->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['batch_id' => 'The Batch parent Program Offering must remain ACTIVE.']);
            }

            if ($batch->offering?->intake?->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['batch_id' => 'The Batch parent Intake / Seat Capacity must remain ACTIVE.']);
            }
        }

        return $batch;
    }

    private function assertUnique(Batch $batch, string $code, ?int $ignoreId = null): void
    {
        $query = Section::query()
            ->where('batch_id', $batch->id)
            ->whereRaw('LOWER(code) = ?', [strtolower(trim($code))]);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages(['code' => 'This Batch already has a Section with the same code.']);
        }
    }

    private function assertCollegeActive(College $college): void
    {
        if ($college->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['college' => 'Section Management cannot be changed while this College is inactive.']);
        }
    }

    private function assertOwned(Section $section, College $college): void
    {
        $owned = Batch::query()
            ->whereKey($section->batch_id)
            ->whereHas('offering', fn ($q) => $q->where('college_id', $college->id))
            ->exists();

        abort_unless($owned, 404);
    }

    private function hasDownstreamReferences(int $sectionId): bool
    {
        return Schema::hasTable('student_enrollments')
            && Schema::hasColumn('student_enrollments', 'section_id')
            && DB::table('student_enrollments')->where('section_id', $sectionId)->exists();
    }

    private function hasActiveDownstreamReferences(int $sectionId): bool
    {
        if (! Schema::hasTable('student_enrollments') || ! Schema::hasColumn('student_enrollments', 'section_id')) {
            return false;
        }

        $query = DB::table('student_enrollments')->where('section_id', $sectionId);
        if (Schema::hasColumn('student_enrollments', 'status')) {
            $query->whereIn('status', ['ACTIVE', 'ENROLLED']);
        }

        return $query->exists();
    }

    private function audit(string $event, Section $section, College $college, int $actorId, ?string $ip, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => $event,
            'resource_type' => 'Section',
            'resource_id' => $section->id,
            'scope_type' => 'COLLEGE',
            'scope_reference' => 'college:'.$college->id,
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
