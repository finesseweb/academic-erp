<?php

namespace App\Services;

use App\Models\Curriculum;
use App\Models\CurriculumCourseMapping;
use App\Models\CurriculumSlot;
use App\Models\CurriculumTerm;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CurriculumAmendmentService
{
    public function create(
        Curriculum $source,
        array $data,
        int $actorId
    ): Curriculum {
        $this->assertAmendable($source);
        $this->assertIdentityAvailable($source, $data);

        return DB::transaction(function () use ($source, $data, $actorId) {
            $target = Curriculum::create([
                'parent_curriculum_id' => $source->id,
                'university_id' => $source->university_id,
                'program_template_id' => $source->program_template_id,
                'academic_session_id' => $source->academic_session_id,
                'code' => trim($data['code']),
                'name' => $source->name,
                'version' => trim($data['version']),
                'effective_from' => $data['revision_effective_from']
                    ?: $source->effective_from,
                'effective_to' => $source->effective_to,
                'lifecycle_status' => 'DRAFT',
                'approval_status' => 'NOT_SUBMITTED',
                'revision_type' => $data['revision_type'],
                'revision_reason' => trim($data['revision_reason']),
                'revision_effective_from' =>
                    $data['revision_effective_from'] ?: null,
                'description' => $source->description,
                'structure_validation_hash' => null,
                'structure_validated_at' => null,
                'structure_validated_by' => null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->copyStructure($source, $target, $actorId);

            $this->audit(
                'CURRICULUM_AMENDMENT_CREATED',
                $target,
                [
                    'source_curriculum_id' => $source->id,
                    'source_version' => $source->version,
                    'target_curriculum_id' => $target->id,
                    'target_version' => $target->version,
                    'revision_type' => $target->revision_type,
                    'revision_reason' => $target->revision_reason,
                    'revision_effective_from' =>
                        $target->revision_effective_from?->format('Y-m-d'),
                ],
                $actorId
            );

            return $target->fresh();
        });
    }

    private function assertAmendable(Curriculum $source): void
    {
        if (
            $source->lifecycle_status !== 'ACTIVE' ||
            ($source->approval_status ?? 'NOT_SUBMITTED') !== 'APPROVED'
        ) {
            throw ValidationException::withMessages([
                'curriculum' =>
                    'Only an ACTIVE and APPROVED Curriculum can be amended.',
            ]);
        }

        $approvedSuccessorExists = $source->amendments()
            ->where('approval_status', 'APPROVED')
            ->exists();

        if ($approvedSuccessorExists) {
            throw ValidationException::withMessages([
                'curriculum' =>
                    'Only the current approved Curriculum version can be amended. Open the latest version instead.',
            ]);
        }

        $openAmendmentExists = $source->amendments()
            ->where('lifecycle_status', '!=', 'RETIRED')
            ->exists();

        if ($openAmendmentExists) {
            throw ValidationException::withMessages([
                'curriculum' =>
                    'An amendment already exists for this Curriculum version. Continue, delete, or retire that amendment before starting another one.',
            ]);
        }
    }

    private function assertIdentityAvailable(
        Curriculum $source,
        array $data
    ): void {
        $code = trim($data['code']);
        $version = trim($data['version']);

        $errors = [];

        if (Curriculum::query()
            ->where('university_id', $source->university_id)
            ->where('code', $code)
            ->exists()) {
            $errors['code'] =
                'This Curriculum code already exists for the University.';
        }

        if (Curriculum::query()
            ->where('university_id', $source->university_id)
            ->where('program_template_id', $source->program_template_id)
            ->where('academic_session_id', $source->academic_session_id)
            ->where('version', $version)
            ->exists()) {
            $errors['version'] =
                'This Program Template, Academic Session and Curriculum version already exists.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function copyStructure(
        Curriculum $source,
        Curriculum $target,
        int $actorId
    ): void {
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

            $sourceSlots = CurriculumSlot::query()
                ->where('curriculum_term_id', $sourceTerm->id)
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();

            foreach ($sourceSlots as $sourceSlot) {
                $targetSlot = CurriculumSlot::create([
                    'curriculum_term_id' => $targetTerm->id,
                    'course_category_id' => $sourceSlot->course_category_id,
                    'course_type_id' => $sourceSlot->course_type_id,
                    'credits' => $sourceSlot->credits,
                    'name' => $sourceSlot->name,
                    'display_order' => $sourceSlot->display_order,
                    'selection_mode' => $sourceSlot->selection_mode,
                    'min_selection' => $sourceSlot->min_selection,
                    'max_selection' => $sourceSlot->max_selection,
                    'status' => $sourceSlot->status,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);

                $sourceMappings = CurriculumCourseMapping::query()
                    ->where('curriculum_slot_id', $sourceSlot->id)
                    ->orderByRaw('COALESCE(display_order, 65535)')
                    ->orderBy('id')
                    ->get();

                foreach ($sourceMappings as $sourceMapping) {
                    CurriculumCourseMapping::create([
                        'curriculum_slot_id' => $targetSlot->id,
                        'course_id' => $sourceMapping->course_id,
                        'discipline_id' => $sourceMapping->discipline_id,
                        'specialization_id' =>
                            $sourceMapping->specialization_id,
                        'display_order' => $sourceMapping->display_order,
                        'status' => $sourceMapping->status,
                        'created_by' => $actorId,
                        'updated_by' => $actorId,
                    ]);
                }
            }
        }
    }

    private function audit(
        string $event,
        Curriculum $curriculum,
        array $after,
        int $actorId
    ): void {
        DB::table('audit_logs')->insert([
            'event' => $event,
            'resource_type' => 'curriculum',
            'resource_id' => $curriculum->id,
            'before' => null,
            'after' => json_encode($after),
            'actor_user_id' => $actorId,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
