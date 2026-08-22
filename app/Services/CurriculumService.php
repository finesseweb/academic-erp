<?php

namespace App\Services;

use App\Models\Curriculum;
use App\Models\University;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurriculumService
{
    public function create(University $university, array $data, int $actorId): Curriculum
    {
        $this->validateOwnership($university->id, $data);
        $this->validateUniqueness($university->id, $data);

        return DB::transaction(function () use ($university, $data, $actorId) {
            $curriculum = Curriculum::create([
                ...$data,
                'university_id' => $university->id,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit('CURRICULUM_CREATED', $curriculum, null, $curriculum->toArray(), $actorId);

            return $curriculum;
        });
    }

    public function update(Curriculum $curriculum, array $data, int $actorId): Curriculum
    {
        $this->validateOwnership($curriculum->university_id, $data);
        $this->validateUniqueness($curriculum->university_id, $data, $curriculum->id);

        return DB::transaction(function () use ($curriculum, $data, $actorId) {
            $before = $curriculum->toArray();
            $curriculum->fill([...$data, 'updated_by' => $actorId])->save();
            $curriculum->refresh();

            $this->audit('CURRICULUM_UPDATED', $curriculum, $before, $curriculum->toArray(), $actorId);

            return $curriculum;
        });
    }

    public function retire(Curriculum $curriculum, int $actorId): Curriculum
    {
        return DB::transaction(function () use ($curriculum, $actorId) {
            $before = $curriculum->toArray();
            $curriculum->update([
                'lifecycle_status' => 'RETIRED',
                'updated_by' => $actorId,
            ]);

            $this->audit('CURRICULUM_RETIRED', $curriculum, $before, $curriculum->fresh()->toArray(), $actorId);

            return $curriculum->fresh();
        });
    }


    public function restore(Curriculum $curriculum, int $actorId): Curriculum
    {
        if ($curriculum->lifecycle_status !== 'RETIRED') {
            throw ValidationException::withMessages([
                'curriculum' => 'Only a retired curriculum can be restored.',
            ]);
        }

        $retirementAudit = DB::table('audit_logs')
            ->where('event', 'CURRICULUM_RETIRED')
            ->where('resource_type', 'curriculum')
            ->where('resource_id', $curriculum->id)
            ->orderByDesc('id')
            ->first();

        if (! $retirementAudit || ! $retirementAudit->before) {
            throw ValidationException::withMessages([
                'curriculum' => 'The previous curriculum status could not be verified from audit history.',
            ]);
        }

        $beforeRetirement = json_decode($retirementAudit->before, true);
        $previousStatus = $beforeRetirement['lifecycle_status'] ?? null;

        if (! in_array($previousStatus, ['DRAFT', 'ACTIVE'], true)) {
            throw ValidationException::withMessages([
                'curriculum' => 'The previous curriculum status is not valid for restoration.',
            ]);
        }

        return DB::transaction(function () use ($curriculum, $actorId, $previousStatus) {
            $before = $curriculum->toArray();

            $curriculum->update([
                'lifecycle_status' => $previousStatus,
                'updated_by' => $actorId,
            ]);

            $curriculum->refresh();

            $this->audit(
                'CURRICULUM_RESTORED',
                $curriculum,
                $before,
                $curriculum->toArray(),
                $actorId
            );

            return $curriculum;
        });
    }

    private function validateOwnership(int $universityId, array $data): void
    {
        $programExists = DB::table('program_templates')
            ->where('id', $data['program_template_id'])
            ->where('university_id', $universityId)
            ->where('status', 'ACTIVE')
            ->exists();

        $sessionExists = DB::table('academic_sessions')
            ->where('id', $data['academic_session_id'])
            ->where('university_id', $universityId)
            ->where('status', 'ACTIVE')
            ->exists();

        if (! $programExists || ! $sessionExists) {
            throw ValidationException::withMessages([
                'program_template_id' => ! $programExists ? 'Select an active Program Template from this University.' : null,
                'academic_session_id' => ! $sessionExists ? 'Select an active Academic Session from this University.' : null,
            ]);
        }
    }

    private function validateUniqueness(int $universityId, array $data, ?int $ignoreId = null): void
    {
        $codeQuery = Curriculum::query()
            ->where('university_id', $universityId)
            ->where('code', $data['code']);

        $versionQuery = Curriculum::query()
            ->where('university_id', $universityId)
            ->where('program_template_id', $data['program_template_id'])
            ->where('academic_session_id', $data['academic_session_id'])
            ->where('version', $data['version']);

        if ($ignoreId) {
            $codeQuery->whereKeyNot($ignoreId);
            $versionQuery->whereKeyNot($ignoreId);
        }

        $errors = [];
        if ($codeQuery->exists()) $errors['code'] = 'This curriculum code already exists for the University.';
        if ($versionQuery->exists()) $errors['version'] = 'This Program Template, Academic Session and curriculum version already exists.';
        if ($errors) throw ValidationException::withMessages($errors);
    }

    private function audit(string $event, Curriculum $curriculum, ?array $before, array $after, int $actorId): void
    {
        DB::table('audit_logs')->insert([
            'event' => $event,
            'resource_type' => 'curriculum',
            'resource_id' => $curriculum->id,
            'before' => $before ? json_encode($before) : null,
            'after' => json_encode($after),
            'actor_user_id' => $actorId,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
