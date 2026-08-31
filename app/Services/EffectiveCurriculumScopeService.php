<?php

namespace App\Services;

use App\Models\CollegeProgramOffering;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EffectiveCurriculumScopeService
{
    public function options(CollegeProgramOffering $offering): array
    {
        $offering->loadMissing(['curriculum:id', 'programTemplate:id']);

        if (! $offering->curriculum_id || ! $offering->program_template_id) {
            return [];
        }

        $mappedDisciplineIds = DB::table('curriculum_course_mappings as ccm')
            ->join('curriculum_slots as cs', 'cs.id', '=', 'ccm.curriculum_slot_id')
            ->join('curriculum_terms as ct', 'ct.id', '=', 'cs.curriculum_term_id')
            ->join('courses as c', 'c.id', '=', 'ccm.course_id')
            ->where('ct.curriculum_id', $offering->curriculum_id)
            ->where('ccm.status', 'ACTIVE')
            ->where('cs.status', 'ACTIVE')
            ->where('ct.status', 'ACTIVE')
            ->where('c.status', 'ACTIVE')
            ->whereNotNull('ccm.discipline_id')
            ->distinct()
            ->pluck('ccm.discipline_id')
            ->map(fn ($id) => (int) $id);

        $templateDisciplineIds = DB::table('program_template_disciplines')
            ->where('program_template_id', $offering->program_template_id)
            ->pluck('discipline_id')
            ->map(fn ($id) => (int) $id);

        // Program Template remains the authoring authority for the applicant's main
        // Discipline. When the Curriculum has explicit Discipline-scoped mappings,
        // use the intersection. When all Curriculum mappings are program/common scope,
        // keep the configured Program Template disciplines selectable instead of
        // incorrectly showing "No Discipline configured".
        $effectiveDisciplineIds = $mappedDisciplineIds->isNotEmpty()
            ? $templateDisciplineIds->intersect($mappedDisciplineIds)->values()
            : $templateDisciplineIds->values();

        if ($effectiveDisciplineIds->isEmpty() && $mappedDisciplineIds->isNotEmpty()) {
            $effectiveDisciplineIds = $mappedDisciplineIds->values();
        }

        if ($effectiveDisciplineIds->isEmpty()) {
            return [];
        }

        $disciplines = DB::table('program_template_disciplines as ptd')
            ->join('academic_disciplines as d', 'd.id', '=', 'ptd.discipline_id')
            ->where('ptd.program_template_id', $offering->program_template_id)
            ->whereIn('ptd.discipline_id', $effectiveDisciplineIds)
            ->where('d.kind', 'DISCIPLINE')
            ->where('d.status', 'ACTIVE')
            ->orderBy('d.display_order')
            ->orderBy('d.name')
            ->get([
                'ptd.id as mapping_id',
                'ptd.specialization_required',
                'd.id',
                'd.name',
                'd.code',
            ]);

        if ($disciplines->isEmpty() && $mappedDisciplineIds->isNotEmpty()) {
            $disciplines = DB::table('academic_disciplines as d')
                ->whereIn('d.id', $mappedDisciplineIds)
                ->where('d.kind', 'DISCIPLINE')
                ->where('d.status', 'ACTIVE')
                ->orderBy('d.display_order')
                ->orderBy('d.name')
                ->get(['d.id', 'd.name', 'd.code'])
                ->map(function ($row) {
                    $row->mapping_id = null;
                    $row->specialization_required = false;
                    return $row;
                });
        }

        return $disciplines->map(function ($discipline) use ($offering) {
            $specializations = $discipline->mapping_id
                ? DB::table('program_template_discipline_specializations as ptds')
                ->join('academic_disciplines as s', 's.id', '=', 'ptds.specialization_id')
                ->where('ptds.program_template_discipline_id', $discipline->mapping_id)
                ->where('s.kind', 'SPECIALIZATION')
                ->where('s.status', 'ACTIVE')
                ->whereExists(function ($query) use ($offering, $discipline) {
                    $query->selectRaw('1')
                        ->from('curriculum_course_mappings as ccm')
                        ->join('curriculum_slots as cs', 'cs.id', '=', 'ccm.curriculum_slot_id')
                        ->join('curriculum_terms as ct', 'ct.id', '=', 'cs.curriculum_term_id')
                        ->join('courses as c', 'c.id', '=', 'ccm.course_id')
                        ->whereColumn('ccm.specialization_id', 's.id')
                        ->where('ccm.discipline_id', $discipline->id)
                        ->where('ct.curriculum_id', $offering->curriculum_id)
                        ->where('ccm.status', 'ACTIVE')
                        ->where('cs.status', 'ACTIVE')
                        ->where('ct.status', 'ACTIVE')
                        ->where('c.status', 'ACTIVE');
                })
                ->orderBy('s.display_order')
                ->orderBy('s.name')
                ->get(['s.id', 's.parent_id', 's.name', 's.code'])
                ->map(fn ($row) => [
                    'id' => (int) $row->id,
                    'parent_id' => $row->parent_id ? (int) $row->parent_id : null,
                    'name' => $row->name,
                    'code' => $row->code,
                ])
                ->values()
                ->all()
                : [];

            return [
                'mapping_id' => (int) $discipline->mapping_id,
                'id' => (int) $discipline->id,
                'name' => $discipline->name,
                'code' => $discipline->code,
                'specialization_required' =>
                    (bool) $discipline->specialization_required && count($specializations) > 0,
                'specializations' => $specializations,
            ];
        })->values()->all();
    }

    public function discipline(CollegeProgramOffering $offering, int $disciplineId): ?array
    {
        return collect($this->options($offering))->firstWhere('id', $disciplineId);
    }

    public function specialization(
        CollegeProgramOffering $offering,
        int $disciplineId,
        int $specializationId
    ): ?array {
        $discipline = $this->discipline($offering, $disciplineId);

        if (! $discipline) {
            return null;
        }

        return collect($discipline['specializations'] ?? [])
            ->firstWhere('id', $specializationId);
    }

    public function assertPreference(
        CollegeProgramOffering $offering,
        ?int $disciplineId,
        ?int $specializationId
    ): void {
        $options = collect($this->options($offering));

        if ($options->isEmpty()) {
            if ($disciplineId || $specializationId) {
                throw ValidationException::withMessages([
                    'academic_preference.discipline_id' =>
                        'This Program Offering has no Discipline-specific course structure in its current Curriculum.',
                ]);
            }

            return;
        }

        if (! $disciplineId) {
            throw ValidationException::withMessages([
                'academic_preference.discipline_id' =>
                    'Select a Discipline that is actually used by the current Curriculum.',
            ]);
        }

        $discipline = $options->firstWhere('id', $disciplineId);
        if (! $discipline) {
            throw ValidationException::withMessages([
                'academic_preference.discipline_id' =>
                    'The selected Discipline has no active course mapping in this Program Offering Curriculum.',
            ]);
        }

        $specializations = collect($discipline['specializations'] ?? []);

        if (($discipline['specialization_required'] ?? false) && ! $specializationId) {
            throw ValidationException::withMessages([
                'academic_preference.specialization_id' =>
                    'Select a Specialization offered by the current Curriculum.',
            ]);
        }

        if ($specializationId && ! $specializations->firstWhere('id', $specializationId)) {
            throw ValidationException::withMessages([
                'academic_preference.specialization_id' =>
                    'The selected Specialization has no active course mapping in this Program Offering Curriculum.',
            ]);
        }
    }
}
