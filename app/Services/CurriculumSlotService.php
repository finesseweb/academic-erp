<?php

namespace App\Services;

use App\Models\Curriculum;
use App\Models\CurriculumSlot;
use App\Models\CurriculumTerm;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurriculumSlotService
{
    public function create(
        Curriculum $curriculum,
        CurriculumTerm $term,
        array $data,
        int $actorId
    ): CurriculumSlot {
        $this->assertTermBelongsToCurriculum($curriculum, $term);
        $this->assertStructureEditable($curriculum);
        $this->assertCourseCategoryBelongsToUniversity(
            $curriculum,
            (int) $data['course_category_id']
        );
        $this->assertCourseTypeBelongsToUniversity(
            $curriculum,
            (int) $data['course_type_id']
        );
        $data = $this->normalizeSelectionRules($data);
        $this->assertDisplayOrderUnique(
            $term,
            (int) $data['display_order']
        );

        return DB::transaction(function () use ($term, $data, $actorId) {
            $slot = CurriculumSlot::create([
                'curriculum_term_id' => $term->id,
                'course_category_id' => (int) $data['course_category_id'],
                'course_type_id' => (int) $data['course_type_id'],
                'credits' => number_format((float) $data['credits'], 2, '.', ''),
                'name' => trim($data['name']),
                'display_order' => (int) $data['display_order'],
                'selection_mode' => $data['selection_mode'],
                'min_selection' => $data['min_selection'],
                'max_selection' => $data['max_selection'],
                'status' => 'ACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit(
                'CURRICULUM_SLOT_CREATED',
                $slot,
                null,
                $slot->toArray(),
                $actorId
            );

            return $slot;
        });
    }

    public function update(
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot,
        array $data,
        int $actorId
    ): CurriculumSlot {
        $this->assertTermBelongsToCurriculum($curriculum, $term);
        $this->assertSlotBelongsToTerm($term, $slot);
        $this->assertStructureEditable($curriculum);
        $this->assertCourseCategoryBelongsToUniversity(
            $curriculum,
            (int) $data['course_category_id']
        );
        $this->assertCourseTypeBelongsToUniversity(
            $curriculum,
            (int) $data['course_type_id']
        );
        $data = $this->normalizeSelectionRules($data);
        $this->assertDisplayOrderUnique(
            $term,
            (int) $data['display_order'],
            $slot->id
        );

        return DB::transaction(function () use ($slot, $data, $actorId) {
            $before = $slot->toArray();

            $slot->update([
                'course_category_id' => (int) $data['course_category_id'],
                'course_type_id' => (int) $data['course_type_id'],
                'credits' => number_format((float) $data['credits'], 2, '.', ''),
                'name' => trim($data['name']),
                'display_order' => (int) $data['display_order'],
                'selection_mode' => $data['selection_mode'],
                'min_selection' => $data['min_selection'],
                'max_selection' => $data['max_selection'],
                'updated_by' => $actorId,
            ]);

            $slot->refresh();

            $this->audit(
                'CURRICULUM_SLOT_UPDATED',
                $slot,
                $before,
                $slot->toArray(),
                $actorId
            );

            return $slot;
        });
    }

    public function setStatus(
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot,
        string $status,
        int $actorId
    ): CurriculumSlot {
        $this->assertTermBelongsToCurriculum($curriculum, $term);
        $this->assertSlotBelongsToTerm($term, $slot);
        $this->assertStructureEditable($curriculum);

        if (! in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid Curriculum Slot status.',
            ]);
        }

        return DB::transaction(function () use ($slot, $status, $actorId) {
            $before = $slot->toArray();

            $slot->update([
                'status' => $status,
                'updated_by' => $actorId,
            ]);

            $slot->refresh();

            $this->audit(
                'CURRICULUM_SLOT_STATUS_CHANGED',
                $slot,
                $before,
                $slot->toArray(),
                $actorId
            );

            return $slot;
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

    private function assertTermBelongsToCurriculum(
        Curriculum $curriculum,
        CurriculumTerm $term
    ): void {
        if ((int) $term->curriculum_id !== (int) $curriculum->id) {
            abort(404);
        }
    }

    private function assertSlotBelongsToTerm(
        CurriculumTerm $term,
        CurriculumSlot $slot
    ): void {
        if ((int) $slot->curriculum_term_id !== (int) $term->id) {
            abort(404);
        }
    }

    private function assertCourseCategoryBelongsToUniversity(
        Curriculum $curriculum,
        int $courseCategoryId
    ): void {
        $valid = DB::table('course_categories')
            ->where('id', $courseCategoryId)
            ->where('university_id', $curriculum->university_id)
            ->where('status', 'ACTIVE')
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'course_category_id' => 'Select an active Course Category owned by this University.',
            ]);
        }
    }


    private function assertCourseTypeBelongsToUniversity(
        Curriculum $curriculum,
        int $courseTypeId
    ): void {
        $valid = DB::table('course_types')
            ->where('id', $courseTypeId)
            ->where('university_id', $curriculum->university_id)
            ->where('status', 'ACTIVE')
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'course_type_id' => 'Select an active Course Type owned by this University.',
            ]);
        }
    }

    private function normalizeSelectionRules(array $data): array
    {
        if ($data['selection_mode'] === 'MANDATORY') {
            $data['min_selection'] = null;
            $data['max_selection'] = null;

            return $data;
        }

        $min = (int) $data['min_selection'];
        $max = (int) $data['max_selection'];

        if ($max < $min) {
            throw ValidationException::withMessages([
                'max_selection' => 'Maximum Selection must be greater than or equal to Minimum Selection.',
            ]);
        }

        $data['min_selection'] = $min;
        $data['max_selection'] = $max;

        return $data;
    }

    private function assertDisplayOrderUnique(
        CurriculumTerm $term,
        int $displayOrder,
        ?int $ignoreId = null
    ): void {
        $query = CurriculumSlot::query()
            ->where('curriculum_term_id', $term->id)
            ->where('display_order', $displayOrder);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'display_order' => 'This display order already exists in the selected Term / Semester.',
            ]);
        }
    }

    private function audit(
        string $event,
        CurriculumSlot $slot,
        ?array $before,
        array $after,
        int $actorId
    ): void {
        DB::table('audit_logs')->insert([
            'event' => $event,
            'resource_type' => 'curriculum_slot',
            'resource_id' => $slot->id,
            'before' => $before ? json_encode($before) : null,
            'after' => json_encode($after),
            'actor_user_id' => $actorId,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
