<?php

namespace App\Services;

use App\Models\Curriculum;
use App\Models\CurriculumTerm;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurriculumTermService
{
    public function create(Curriculum $curriculum, array $data, int $actorId): CurriculumTerm
    {
        $this->assertStructureEditable($curriculum);
        $this->assertSequenceUnique($curriculum, (int) $data['sequence_no']);

        return DB::transaction(function () use ($curriculum, $data, $actorId) {
            $term = CurriculumTerm::create([
                'curriculum_id' => $curriculum->id,
                'sequence_no' => (int) $data['sequence_no'],
                'name' => trim($data['name']),
                'status' => 'ACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit(
                'CURRICULUM_TERM_CREATED',
                $term,
                null,
                $term->toArray(),
                $actorId
            );

            return $term;
        });
    }

    public function update(
        Curriculum $curriculum,
        CurriculumTerm $term,
        array $data,
        int $actorId
    ): CurriculumTerm {
        $this->assertBelongsToCurriculum($curriculum, $term);
        $this->assertStructureEditable($curriculum);
        $this->assertSequenceUnique(
            $curriculum,
            (int) $data['sequence_no'],
            $term->id
        );

        return DB::transaction(function () use ($term, $data, $actorId) {
            $before = $term->toArray();

            $term->update([
                'sequence_no' => (int) $data['sequence_no'],
                'name' => trim($data['name']),
                'updated_by' => $actorId,
            ]);

            $term->refresh();

            $this->audit(
                'CURRICULUM_TERM_UPDATED',
                $term,
                $before,
                $term->toArray(),
                $actorId
            );

            return $term;
        });
    }

    public function setStatus(
        Curriculum $curriculum,
        CurriculumTerm $term,
        string $status,
        int $actorId
    ): CurriculumTerm {
        $this->assertBelongsToCurriculum($curriculum, $term);
        $this->assertStructureEditable($curriculum);

        if (! in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid Term / Semester status.',
            ]);
        }

        return DB::transaction(function () use ($term, $status, $actorId) {
            $before = $term->toArray();

            $term->update([
                'status' => $status,
                'updated_by' => $actorId,
            ]);

            $term->refresh();

            $this->audit(
                'CURRICULUM_TERM_STATUS_CHANGED',
                $term,
                $before,
                $term->toArray(),
                $actorId
            );

            return $term;
        });
    }

    private function assertStructureEditable(Curriculum $curriculum): void
    {
        if ($curriculum->lifecycle_status !== 'DRAFT') {
            throw ValidationException::withMessages([
                'curriculum' => 'Only a DRAFT curriculum can change its structure. Active and retired curriculum versions remain historical records.',
            ]);
        }
    }

    private function assertBelongsToCurriculum(
        Curriculum $curriculum,
        CurriculumTerm $term
    ): void {
        if ((int) $term->curriculum_id !== (int) $curriculum->id) {
            abort(404);
        }
    }

    private function assertSequenceUnique(
        Curriculum $curriculum,
        int $sequenceNo,
        ?int $ignoreId = null
    ): void {
        $query = CurriculumTerm::query()
            ->where('curriculum_id', $curriculum->id)
            ->where('sequence_no', $sequenceNo);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'sequence_no' => 'This Term / Semester sequence already exists in the curriculum.',
            ]);
        }
    }

    private function audit(
        string $event,
        CurriculumTerm $term,
        ?array $before,
        array $after,
        int $actorId
    ): void {
        DB::table('audit_logs')->insert([
            'event' => $event,
            'resource_type' => 'curriculum_term',
            'resource_id' => $term->id,
            'before' => $before ? json_encode($before) : null,
            'after' => json_encode($after),
            'actor_user_id' => $actorId,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
