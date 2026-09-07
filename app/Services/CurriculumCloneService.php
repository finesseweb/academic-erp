<?php

namespace App\Services;

use App\Models\Curriculum;
use App\Models\CurriculumCourseMapping;
use App\Models\CurriculumSlot;
use App\Models\CurriculumTerm;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurriculumCloneService
{
    public function __construct(
        private readonly CurriculumStructureValidationService $validationService
    ) {}

    public function cloneEntireCurriculum(
        Curriculum $source,
        array $data,
        int $actorId
    ): Curriculum {
        $validation = $this->validationService->validate($source);

        if (! $validation['valid']) {
            throw ValidationException::withMessages([
                'source' => 'Source Curriculum must pass Validate Structure before the complete structure can be cloned.',
            ]);
        }

        $this->assertAcademicSession(
            $source->university_id,
            (int) $data['academic_session_id']
        );
        $this->assertCurriculumIdentityAvailable(
            $source,
            $data
        );

        return DB::transaction(function () use ($source, $data, $actorId) {
            $target = Curriculum::create([
                'university_id' => $source->university_id,
                'program_template_id' => $source->program_template_id,
                'academic_session_id' => (int) $data['academic_session_id'],
                'code' => trim($data['code']),
                'name' => trim($data['name']),
                'version' => trim($data['version']),
                'effective_from' => $data['effective_from'] ?: null,
                'effective_to' => $data['effective_to'] ?: null,
                'lifecycle_status' => 'DRAFT',
                'description' => $data['description'] ?? null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $sourceTerms = CurriculumTerm::query()
                ->where('curriculum_id', $source->id)
                ->orderBy('sequence_no')
                ->orderBy('id')
                ->get();

            foreach ($sourceTerms as $sourceTerm) {
                $targetTerm = CurriculumTerm::create([
                    'curriculum_id' => $target->id,
                    'sequence_no' => $sourceTerm->sequence_no,
                    'name' => $sourceTerm->name,
                    'status' => $sourceTerm->status,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);

                $this->cloneSlotsIntoTerm(
                    $sourceTerm,
                    $targetTerm,
                    $actorId,
                    true
                );
            }

            $this->audit(
                'CURRICULUM_STRUCTURE_CLONED',
                'curriculum',
                $target->id,
                [
                    'source_curriculum_id' => $source->id,
                    'target_curriculum_id' => $target->id,
                ],
                $actorId
            );

            return $target;
        });
    }

    public function cloneTerm(Curriculum $sourceCurriculum, CurriculumTerm $sourceTerm, array $data, int $actorId): CurriculumTerm
    {
        $this->assertTermBelongsToCurriculum($sourceCurriculum, $sourceTerm);
        $target = Curriculum::query()
            ->where('id', (int) $data['target_curriculum_id'])
            ->where('university_id', $sourceCurriculum->university_id)
            ->where('program_template_id', $sourceCurriculum->program_template_id)
            ->first();

        if (! $target) {
            throw ValidationException::withMessages([
                'target_curriculum_id' => 'Target Curriculum must belong to the same University and Program Template.',
            ]);
        }
        $this->assertDraft($target);

        if (CurriculumTerm::query()->where('curriculum_id', $target->id)->where('sequence_no', (int) $data['sequence_no'])->exists()) {
            throw ValidationException::withMessages([
                'sequence_no' => 'This Term / Semester sequence already exists in the target Curriculum.',
            ]);
        }

        return DB::transaction(function () use ($sourceCurriculum, $target, $sourceTerm, $data, $actorId) {
            $targetTerm = CurriculumTerm::create([
                'curriculum_id' => $target->id,
                'sequence_no' => (int) $data['sequence_no'],
                'name' => trim($data['name']),
                'status' => 'ACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $this->cloneSlotsIntoTerm($sourceTerm, $targetTerm, $actorId, true);
            $this->audit('CURRICULUM_TERM_CLONED', 'curriculum_term', $targetTerm->id, [
                'source_curriculum_id' => $sourceCurriculum->id,
                'target_curriculum_id' => $target->id,
                'source_term_id' => $sourceTerm->id,
                'target_term_id' => $targetTerm->id,
            ], $actorId);
            return $targetTerm;
        });
    }

    public function cloneSlot(Curriculum $sourceCurriculum, CurriculumTerm $sourceTerm, CurriculumSlot $sourceSlot, array $data, int $actorId): CurriculumSlot
    {
        $this->assertTermBelongsToCurriculum($sourceCurriculum, $sourceTerm);
        if ((int) $sourceSlot->curriculum_term_id !== (int) $sourceTerm->id) abort(404);

        $target = Curriculum::query()
            ->where('id', (int) $data['target_curriculum_id'])
            ->where('university_id', $sourceCurriculum->university_id)
            ->where('program_template_id', $sourceCurriculum->program_template_id)
            ->first();

        if (! $target) {
            throw ValidationException::withMessages([
                'target_curriculum_id' => 'Target Curriculum must belong to the same University and Program Template.',
            ]);
        }
        $this->assertDraft($target);

        $targetTerm = CurriculumTerm::query()
            ->where('id', (int) $data['target_term_id'])
            ->where('curriculum_id', $target->id)
            ->first();

        if (! $targetTerm) {
            throw ValidationException::withMessages([
                'target_term_id' => 'Select a Term / Semester from the target Curriculum.',
            ]);
        }

        if (CurriculumSlot::query()->where('curriculum_term_id', $targetTerm->id)->where('display_order', (int) $data['display_order'])->exists()) {
            throw ValidationException::withMessages([
                'display_order' => 'This display order already exists in the target Term / Semester.',
            ]);
        }

        return DB::transaction(function () use ($sourceCurriculum, $target, $sourceSlot, $targetTerm, $data, $actorId) {
            $targetSlot = $this->copySlot($sourceSlot, $targetTerm, $actorId, [
                'name' => trim($data['name']),
                'display_order' => (int) $data['display_order'],
                'status' => 'ACTIVE',
            ]);
            $this->audit('CURRICULUM_SLOT_CLONED', 'curriculum_slot', $targetSlot->id, [
                'source_curriculum_id' => $sourceCurriculum->id,
                'target_curriculum_id' => $target->id,
                'source_slot_id' => $sourceSlot->id,
                'target_slot_id' => $targetSlot->id,
                'target_term_id' => $targetTerm->id,
            ], $actorId);
            return $targetSlot;
        });
    }

    private function cloneSlotsIntoTerm(
        CurriculumTerm $sourceTerm,
        CurriculumTerm $targetTerm,
        int $actorId,
        bool $preserveStatus
    ): void {
        $slots = CurriculumSlot::query()
            ->where('curriculum_term_id', $sourceTerm->id)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        foreach ($slots as $sourceSlot) {
            $this->copySlot(
                $sourceSlot,
                $targetTerm,
                $actorId,
                [
                    'status' => $preserveStatus
                        ? $sourceSlot->status
                        : 'ACTIVE',
                ]
            );
        }
    }

    private function copySlot(
        CurriculumSlot $sourceSlot,
        CurriculumTerm $targetTerm,
        int $actorId,
        array $overrides = []
    ): CurriculumSlot {
        $targetSlot = CurriculumSlot::create([
            'curriculum_term_id' => $targetTerm->id,
            'course_category_id' => $sourceSlot->course_category_id,
            'course_type_id' => $sourceSlot->course_type_id,
            'credits' => $sourceSlot->credits,
            'credit_counting' => $sourceSlot->credit_counting,
            'name' => $overrides['name'] ?? $sourceSlot->name,
            'display_order' => $overrides['display_order']
                ?? $sourceSlot->display_order,
            'selection_mode' => $sourceSlot->selection_mode,
            'min_selection' => $sourceSlot->min_selection,
            'max_selection' => $sourceSlot->max_selection,
            'status' => $overrides['status'] ?? $sourceSlot->status,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ]);

        $mappings = CurriculumCourseMapping::query()
            ->where('curriculum_slot_id', $sourceSlot->id)
            ->orderByRaw('COALESCE(display_order, 65535)')
            ->orderBy('id')
            ->get();

        foreach ($mappings as $mapping) {
            CurriculumCourseMapping::create([
                'curriculum_slot_id' => $targetSlot->id,
                'course_id' => $mapping->course_id,
                'discipline_id' => $mapping->discipline_id,
                'specialization_id' => $mapping->specialization_id,
                'display_order' => $mapping->display_order,
                'status' => $mapping->status,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
        }

        return $targetSlot;
    }

    private function assertDraft(Curriculum $curriculum): void
    {
        if (
            $curriculum->lifecycle_status !== 'DRAFT' ||
            in_array(
                $curriculum->approval_status ?? 'NOT_SUBMITTED',
                ['SUBMITTED', 'UNDER_APPROVAL', 'APPROVED'],
                true
            )
        ) {
            throw ValidationException::withMessages([
                'curriculum' =>
                    'Clone target must be an editable DRAFT Curriculum that is not under approval.',
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

    private function assertAcademicSession(
        int $universityId,
        int $academicSessionId
    ): void {
        $valid = DB::table('academic_sessions')
            ->where('id', $academicSessionId)
            ->where('university_id', $universityId)
            ->where('status', 'ACTIVE')
            ->exists();

        if (! $valid) {
            throw ValidationException::withMessages([
                'academic_session_id' => 'Select an active Academic Session from the same University.',
            ]);
        }
    }

    private function assertCurriculumIdentityAvailable(
        Curriculum $source,
        array $data
    ): void {
        $codeExists = Curriculum::query()
            ->where('university_id', $source->university_id)
            ->where('code', trim($data['code']))
            ->exists();

        $versionExists = Curriculum::query()
            ->where('university_id', $source->university_id)
            ->where('program_template_id', $source->program_template_id)
            ->where('academic_session_id', (int) $data['academic_session_id'])
            ->where('version', trim($data['version']))
            ->exists();

        $errors = [];

        if ($codeExists) {
            $errors['code'] = 'This Curriculum code already exists for the University.';
        }

        if ($versionExists) {
            $errors['version'] = 'This Program Template, Academic Session and Curriculum version already exists.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function audit(
        string $event,
        string $resourceType,
        int $resourceId,
        array $after,
        int $actorId
    ): void {
        DB::table('audit_logs')->insert([
            'event' => $event,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'before' => null,
            'after' => json_encode($after),
            'actor_user_id' => $actorId,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
