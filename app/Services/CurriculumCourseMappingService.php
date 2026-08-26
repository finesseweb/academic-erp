<?php

namespace App\Services;

use App\Models\Curriculum;
use App\Models\CurriculumCourseMapping;
use App\Models\CurriculumSlot;
use App\Models\CurriculumTerm;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurriculumCourseMappingService
{
    public function create(
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot,
        int $disciplineId,
        ?int $specializationId,
        int $courseId,
        int $actorId
    ): CurriculumCourseMapping {
        $this->assertContext($curriculum, $term, $slot);
        $this->assertStructureEditable($curriculum);
        $this->assertProgramAcademicContext(
            $curriculum,
            $disciplineId,
            $specializationId
        );
        $this->assertCompatibleCourse($curriculum, $slot, $courseId);
        $this->assertNotAlreadyMapped(
            $slot,
            $disciplineId,
            $specializationId,
            $courseId
        );

        return DB::transaction(function () use (
            $slot,
            $disciplineId,
            $specializationId,
            $courseId,
            $actorId
        ) {
            $nextOrder = (int) CurriculumCourseMapping::query()
                ->where('curriculum_slot_id', $slot->id)
                ->max('display_order') + 1;

            $mapping = CurriculumCourseMapping::create([
                'curriculum_slot_id' => $slot->id,
                'course_id' => $courseId,
                'discipline_id' => $disciplineId,
                'specialization_id' => $specializationId,
                'display_order' => max($nextOrder, 1),
                'status' => 'ACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit(
                'CURRICULUM_COURSE_MAPPED',
                $mapping,
                null,
                $mapping->toArray(),
                $actorId
            );

            return $mapping;
        });
    }


    public function update(
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot,
        CurriculumCourseMapping $mapping,
        int $disciplineId,
        ?int $specializationId,
        int $courseId,
        int $actorId
    ): CurriculumCourseMapping {
        $this->assertContext($curriculum, $term, $slot);
        $this->assertMappingBelongsToSlot($slot, $mapping);
        $this->assertStructureEditable($curriculum);
        $this->assertProgramAcademicContext($curriculum, $disciplineId, $specializationId);
        $this->assertCompatibleCourse($curriculum, $slot, $courseId);
        $this->assertNotAlreadyMapped(
            $slot,
            $disciplineId,
            $specializationId,
            $courseId,
            $mapping->id
        );

        return DB::transaction(function () use (
            $mapping,
            $disciplineId,
            $specializationId,
            $courseId,
            $actorId
        ) {
            $before = $mapping->toArray();

            $mapping->update([
                'discipline_id' => $disciplineId,
                'specialization_id' => $specializationId,
                'course_id' => $courseId,
                'updated_by' => $actorId,
            ]);

            $mapping->refresh();

            $this->audit(
                'CURRICULUM_COURSE_MAPPING_UPDATED',
                $mapping,
                $before,
                $mapping->toArray(),
                $actorId
            );

            return $mapping;
        });
    }

    public function setStatus(
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot,
        CurriculumCourseMapping $mapping,
        string $status,
        int $actorId
    ): CurriculumCourseMapping {
        $this->assertContext($curriculum, $term, $slot);
        $this->assertMappingBelongsToSlot($slot, $mapping);
        $this->assertStructureEditable($curriculum);

        if (! in_array($status, ['ACTIVE', 'INACTIVE'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid Course / Paper Mapping status.',
            ]);
        }

        return DB::transaction(function () use ($mapping, $status, $actorId) {
            $before = $mapping->toArray();

            $mapping->update([
                'status' => $status,
                'updated_by' => $actorId,
            ]);

            $mapping->refresh();

            $this->audit(
                'CURRICULUM_COURSE_MAPPING_STATUS_CHANGED',
                $mapping,
                $before,
                $mapping->toArray(),
                $actorId
            );

            return $mapping;
        });
    }

    public function updateDisplayOrder(
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot,
        CurriculumCourseMapping $mapping,
        int $displayOrder,
        int $actorId
    ): CurriculumCourseMapping {
        $this->assertContext($curriculum, $term, $slot);
        $this->assertMappingBelongsToSlot($slot, $mapping);
        $this->assertStructureEditable($curriculum);

        return DB::transaction(function () use (
            $slot,
            $mapping,
            $displayOrder,
            $actorId
        ) {
            $before = $mapping->toArray();

            $ordered = CurriculumCourseMapping::query()
                ->where('curriculum_slot_id', $slot->id)
                ->whereKeyNot($mapping->id)
                ->orderByRaw('COALESCE(display_order, 65535)')
                ->orderBy('id')
                ->get();

            $targetIndex = min(
                max($displayOrder - 1, 0),
                $ordered->count()
            );

            $ordered->splice($targetIndex, 0, [$mapping]);

            foreach ($ordered->values() as $index => $item) {
                $item->update([
                    'display_order' => $index + 1,
                    'updated_by' => $actorId,
                ]);
            }

            $mapping->refresh();

            $this->audit(
                'CURRICULUM_COURSE_MAPPING_ORDER_CHANGED',
                $mapping,
                $before,
                $mapping->toArray(),
                $actorId
            );

            return $mapping;
        });
    }

    private function assertProgramAcademicContext(
        Curriculum $curriculum,
        int $disciplineId,
        ?int $specializationId
    ): void {
        $templateDiscipline = DB::table('program_template_disciplines as ptd')
            ->join(
                'academic_disciplines as discipline',
                'discipline.id',
                '=',
                'ptd.discipline_id'
            )
            ->where(
                'ptd.program_template_id',
                $curriculum->program_template_id
            )
            ->where('ptd.discipline_id', $disciplineId)
            ->where('discipline.university_id', $curriculum->university_id)
            ->where('discipline.kind', 'DISCIPLINE')
            ->where('discipline.status', 'ACTIVE')
            ->first(['ptd.id']);

        if (! $templateDiscipline) {
            throw ValidationException::withMessages([
                'discipline_id' => 'Select an active Discipline included in this Program Template.',
            ]);
        }

        if ($specializationId === null) {
            return;
        }

        $valid = DB::table(
            'program_template_discipline_specializations as ptds'
        )
            ->join(
                'academic_disciplines as specialization',
                'specialization.id',
                '=',
                'ptds.specialization_id'
            )
            ->where(
                'ptds.program_template_discipline_id',
                $templateDiscipline->id
            )
            ->where('ptds.specialization_id', $specializationId)
            ->where('specialization.university_id', $curriculum->university_id)
            ->where('specialization.kind', 'SPECIALIZATION')
            ->where('specialization.parent_id', $disciplineId)
            ->where('specialization.status', 'ACTIVE')
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'specialization_id' => 'Select an active Specialization allowed under the selected Program Template Discipline.',
            ]);
        }
    }

    private function assertCompatibleCourse(
        Curriculum $curriculum,
        CurriculumSlot $slot,
        int $courseId
    ): void {
        $course = DB::table('courses')
            ->where('id', $courseId)
            ->where('university_id', $curriculum->university_id)
            ->where('status', 'ACTIVE')
            ->first([
                'id',
                'course_category_id',
                'course_type_id',
            ]);

        if (! $course) {
            throw ValidationException::withMessages([
                'course_id' => 'Select an active Course / Subject owned by this University.',
            ]);
        }

        if ((int) $course->course_category_id !== (int) $slot->course_category_id) {
            throw ValidationException::withMessages([
                'course_id' => 'The selected Course / Subject does not match the Slot Course Category.',
            ]);
        }

        if ((int) $course->course_type_id !== (int) $slot->course_type_id) {
            throw ValidationException::withMessages([
                'course_id' => 'The selected Course / Subject does not match the Slot Course Type.',
            ]);
        }
    }

    private function assertNotAlreadyMapped(
        CurriculumSlot $slot,
        int $disciplineId,
        ?int $specializationId,
        int $courseId,
        ?int $ignoreMappingId = null
    ): void {
        $query = CurriculumCourseMapping::query()
            ->where('curriculum_slot_id', $slot->id)
            ->where('discipline_id', $disciplineId)
            ->where('course_id', $courseId);

        if ($specializationId === null) {
            $query->whereNull('specialization_id');
        } else {
            $query->where('specialization_id', $specializationId);
        }

        if ($ignoreMappingId !== null) {
            $query->whereKeyNot($ignoreMappingId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'course_id' => 'This Course / Subject is already mapped to the selected Discipline / Specialization in this Slot.',
            ]);
        }
    }

    private function assertContext(
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot
    ): void {
        if ((int) $term->curriculum_id !== (int) $curriculum->id) {
            abort(404);
        }

        if ((int) $slot->curriculum_term_id !== (int) $term->id) {
            abort(404);
        }
    }

    private function assertMappingBelongsToSlot(
        CurriculumSlot $slot,
        CurriculumCourseMapping $mapping
    ): void {
        if ((int) $mapping->curriculum_slot_id !== (int) $slot->id) {
            abort(404);
        }
    }

    private function assertStructureEditable(Curriculum $curriculum): void
    {
        if ($curriculum->lifecycle_status !== 'DRAFT') {
            throw ValidationException::withMessages([
                'curriculum' => 'Only a DRAFT curriculum can change Course / Paper Mapping.',
            ]);
        }
    }

    private function audit(
        string $event,
        CurriculumCourseMapping $mapping,
        ?array $before,
        array $after,
        int $actorId
    ): void {
        DB::table('audit_logs')->insert([
            'event' => $event,
            'resource_type' => 'curriculum_course_mapping',
            'resource_id' => $mapping->id,
            'before' => $before ? json_encode($before) : null,
            'after' => json_encode($after),
            'actor_user_id' => $actorId,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
