<?php

namespace App\Services;

use App\Models\Curriculum;
use App\Models\CurriculumCourseMapping;
use App\Models\CurriculumSlot;
use App\Models\CurriculumTerm;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurriculumStructureDeleteService
{

    public function deleteCurriculum(Curriculum $curriculum, int $actorId): void
    {
        $this->assertDraftAndUnassigned($curriculum);

        DB::transaction(function () use ($curriculum, $actorId) {
            $before = $curriculum->toArray();

            $termIds = CurriculumTerm::query()
                ->where('curriculum_id', $curriculum->id)
                ->pluck('id');

            $slotIds = CurriculumSlot::query()
                ->whereIn('curriculum_term_id', $termIds)
                ->pluck('id');

            $mappingCount = CurriculumCourseMapping::query()
                ->whereIn('curriculum_slot_id', $slotIds)
                ->count();

            CurriculumCourseMapping::query()
                ->whereIn('curriculum_slot_id', $slotIds)
                ->delete();

            $slotCount = CurriculumSlot::query()
                ->whereIn('id', $slotIds)
                ->count();

            CurriculumSlot::query()
                ->whereIn('id', $slotIds)
                ->delete();

            $termCount = CurriculumTerm::query()
                ->whereIn('id', $termIds)
                ->count();

            CurriculumTerm::query()
                ->whereIn('id', $termIds)
                ->delete();

            $curriculumId = $curriculum->id;

            DB::table('audit_logs')->insert([
                'event' => 'CURRICULUM_DELETED',
                'resource_type' => 'curriculum',
                'resource_id' => $curriculumId,
                'before' => json_encode([
                    ...$before,
                    'deleted_term_count' => $termCount,
                    'deleted_slot_count' => $slotCount,
                    'deleted_mapping_count' => $mappingCount,
                ]),
                'after' => null,
                'actor_user_id' => $actorId,
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);

            $curriculum->delete();
        });
    }

    public function deleteTerm(Curriculum $curriculum, CurriculumTerm $term, int $actorId): void
    {
        $this->assertDraftAndUnassigned($curriculum);
        $this->assertTerm($curriculum, $term);

        DB::transaction(function () use ($term, $actorId) {
            $slotIds = CurriculumSlot::query()
                ->where('curriculum_term_id', $term->id)
                ->pluck('id');

            CurriculumCourseMapping::query()
                ->whereIn('curriculum_slot_id', $slotIds)
                ->delete();

            CurriculumSlot::query()
                ->whereIn('id', $slotIds)
                ->delete();

            $termId = $term->id;
            $term->delete();

            $this->audit('CURRICULUM_TERM_DELETED', 'curriculum_term', $termId, $actorId);
        });
    }

    public function deleteSlot(
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot,
        int $actorId
    ): void {
        $this->assertDraftAndUnassigned($curriculum);
        $this->assertTerm($curriculum, $term);
        $this->assertSlot($term, $slot);

        DB::transaction(function () use ($slot, $actorId) {
            CurriculumCourseMapping::query()
                ->where('curriculum_slot_id', $slot->id)
                ->delete();

            $slotId = $slot->id;
            $slot->delete();

            $this->audit('CURRICULUM_SLOT_DELETED', 'curriculum_slot', $slotId, $actorId);
        });
    }

    public function deleteMapping(
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot,
        CurriculumCourseMapping $mapping,
        int $actorId
    ): void {
        $this->assertDraftAndUnassigned($curriculum);
        $this->assertTerm($curriculum, $term);
        $this->assertSlot($term, $slot);

        if ((int) $mapping->curriculum_slot_id !== (int) $slot->id) {
            abort(404);
        }

        DB::transaction(function () use ($mapping, $actorId) {
            $mappingId = $mapping->id;
            $mapping->delete();

            $this->audit(
                'CURRICULUM_COURSE_MAPPING_DELETED',
                'curriculum_course_mapping',
                $mappingId,
                $actorId
            );
        });
    }

    private function assertDraftAndUnassigned(Curriculum $curriculum): void
    {
        if ($curriculum->lifecycle_status !== 'DRAFT') {
            throw ValidationException::withMessages([
                'curriculum' => 'Delete is allowed only while the Curriculum is DRAFT.',
            ]);
        }

        /*
         * Student-group assignment guard:
         * The assignment module is not implemented yet, so there is no
         * canonical assignment table to query today.
         *
         * IMPORTANT: when Curriculum -> Student Group assignment is built,
         * add its canonical existence check here. The UI rule is already
         * documented: once assigned, delete must be hidden/blocked.
         */
    }

    private function assertTerm(Curriculum $curriculum, CurriculumTerm $term): void
    {
        if ((int) $term->curriculum_id !== (int) $curriculum->id) {
            abort(404);
        }
    }

    private function assertSlot(CurriculumTerm $term, CurriculumSlot $slot): void
    {
        if ((int) $slot->curriculum_term_id !== (int) $term->id) {
            abort(404);
        }
    }

    private function audit(string $event, string $type, int $id, int $actorId): void
    {
        DB::table('audit_logs')->insert([
            'event' => $event,
            'resource_type' => $type,
            'resource_id' => $id,
            'before' => null,
            'after' => null,
            'actor_user_id' => $actorId,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
