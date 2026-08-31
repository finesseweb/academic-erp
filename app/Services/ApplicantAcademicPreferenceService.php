<?php

namespace App\Services;

use App\Models\CollegeAdmissionApplication;
use App\Models\CollegeAdmissionCycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicantAcademicPreferenceService
{
    public function __construct(
        private EffectiveCurriculumScopeService $effectiveScope,
    ) {
    }

    public function options(CollegeAdmissionCycle $cycle): array
    {
        $cycle->loadMissing(['programOffering.curriculum', 'programOffering.programTemplate']);
        $offering = $cycle->programOffering;
        if (! $offering || ! $offering->curriculum || ! $offering->programTemplate) {
            return ['disciplines' => [], 'terms' => [], 'curriculum' => null];
        }

        $disciplines = $this->effectiveScope->options($offering);

        $terms = DB::table('curriculum_terms')
            ->where('curriculum_id', $offering->curriculum_id)
            ->where('status', 'ACTIVE')
            ->orderBy('sequence_no')->orderBy('id')
            ->get(['id', 'name', 'sequence_no'])
            ->map(function ($term) {
                $slots = DB::table('curriculum_slots as slot')
                    ->leftJoin('course_categories as category', 'category.id', '=', 'slot.course_category_id')
                    ->leftJoin('course_types as type', 'type.id', '=', 'slot.course_type_id')
                    ->where('slot.curriculum_term_id', $term->id)
                    ->where('slot.status', 'ACTIVE')
                    ->orderBy('slot.display_order')->orderBy('slot.id')
                    ->get([
                        'slot.id', 'slot.name', 'slot.selection_mode', 'slot.min_selection', 'slot.max_selection',
                        'slot.credits', 'slot.display_order', 'category.name as category_name', 'type.name as type_name',
                    ])
                    ->map(function ($slot) {
                        $mappings = DB::table('curriculum_course_mappings as mapping')
                            ->join('courses as course', 'course.id', '=', 'mapping.course_id')
                            ->leftJoin('academic_disciplines as source_discipline', 'source_discipline.id', '=', 'mapping.source_discipline_id')
                            ->where('mapping.curriculum_slot_id', $slot->id)
                            ->where('mapping.status', 'ACTIVE')
                            ->where('course.status', 'ACTIVE')
                            ->orderByRaw('COALESCE(mapping.display_order, 65535)')->orderBy('mapping.id')
                            ->get([
                                'mapping.id', 'mapping.course_id', 'mapping.discipline_id', 'mapping.specialization_id',
                                'course.name as course_name', 'course.code as course_code',
                                'source_discipline.id as source_discipline_id', 'source_discipline.name as source_discipline_name', 'source_discipline.code as source_discipline_code',
                            ])->map(fn ($row) => [
                                'id' => (int) $row->id,
                                'course_id' => (int) $row->course_id,
                                'discipline_id' => $row->discipline_id ? (int) $row->discipline_id : null,
                                'specialization_id' => $row->specialization_id ? (int) $row->specialization_id : null,
                                'course_name' => $row->course_name,
                                'course_code' => $row->course_code,
                                'source_discipline_id' => $row->source_discipline_id ? (int) $row->source_discipline_id : null,
                                'source_discipline_name' => $row->source_discipline_name,
                                'source_discipline_code' => $row->source_discipline_code,
                            ])->values()->all();

                        return [
                            'id' => (int) $slot->id,
                            'name' => $slot->name,
                            'selection_mode' => strtoupper((string) $slot->selection_mode),
                            'min_selection' => $slot->min_selection === null ? null : (int) $slot->min_selection,
                            'max_selection' => $slot->max_selection === null ? null : (int) $slot->max_selection,
                            'credits' => $slot->credits === null ? null : (string) $slot->credits,
                            'category_name' => $slot->category_name,
                            'type_name' => $slot->type_name,
                            'mappings' => $mappings,
                        ];
                    })->values()->all();
                return ['id' => (int) $term->id, 'name' => $term->name, 'sequence_no' => (int) $term->sequence_no, 'slots' => $slots];
            })->values()->all();

        return [
            'curriculum' => [
                'id' => (int) $offering->curriculum->id,
                'name' => $offering->curriculum->name,
                'code' => $offering->curriculum->code,
                'version' => $offering->curriculum->version,
            ],
            'disciplines' => $disciplines,
            'terms' => $terms,
        ];
    }

    public function resolve(CollegeAdmissionCycle $cycle, array $input): array
    {
        $options = $this->options($cycle);
        if (! $options['curriculum']) {
            throw ValidationException::withMessages([
                'academic_preference' => 'The selected Program Offering does not have an active Curriculum configured yet.',
            ]);
        }

        $disciplines = collect($options['disciplines']);
        $disciplineId = (int) ($input['discipline_id'] ?? 0);
        $discipline = $disciplines->firstWhere('id', $disciplineId);
        if ($disciplines->isNotEmpty() && ! $discipline) {
            throw ValidationException::withMessages([
                'academic_preference.discipline_id' => 'Select a Discipline available in this Program Offering.',
            ]);
        }

        $specializationId = filled($input['specialization_id'] ?? null)
            ? (int) $input['specialization_id']
            : null;
        $specializations = collect($discipline['specializations'] ?? []);
        if (($discipline['specialization_required'] ?? false) && $specializations->isNotEmpty() && ! $specializationId) {
            throw ValidationException::withMessages([
                'academic_preference.specialization_id' => 'Select a Specialization for the selected Discipline.',
            ]);
        }
        if ($specializationId && ! $specializations->firstWhere('id', $specializationId)) {
            throw ValidationException::withMessages([
                'academic_preference.specialization_id' => 'Select a Specialization available under the selected Discipline.',
            ]);
        }

        $submittedChoices = collect($input['course_choices'] ?? [])->mapWithKeys(
            fn ($ids, $slotId) => [
                (int) $slotId => collect((array) $ids)
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all(),
            ]
        );

        $resolvedCourses = [];
        $choiceSlots = collect();

        foreach ($options['terms'] as $term) {
            foreach ($term['slots'] as $slot) {
                $applicable = collect($slot['mappings'])->filter(function ($mapping) use ($disciplineId, $specializationId) {
                    if ($mapping['discipline_id'] === null) {
                        return $mapping['specialization_id'] === null;
                    }
                    if ((int) $mapping['discipline_id'] !== $disciplineId) {
                        return false;
                    }
                    if ($mapping['specialization_id'] === null) {
                        return true;
                    }
                    return $specializationId !== null
                        && (int) $mapping['specialization_id'] === $specializationId;
                })->values();

                if ($slot['selection_mode'] === 'MANDATORY') {
                    foreach ($applicable as $mapping) {
                        $resolvedCourses[] = $this->courseRow($term, $slot, $mapping, 'AUTO_MANDATORY');
                    }
                    continue;
                }

                if ($slot['selection_mode'] === 'CHOICE' && $applicable->isNotEmpty()) {
                    $choiceSlots->push([
                        'term' => $term,
                        'slot' => $slot,
                        'mappings' => $applicable,
                    ]);
                }
            }
        }

        // Applicant-facing selection is category-first, not semester-first. If every
        // slot in the category can be completely satisfied by choosing one Offered From
        // Discipline, that discipline behaves as an academic package. The UI may show
        // only "History", while all of History's underlying term papers remain linked.
        $processedSlotIds = [];
        foreach ($choiceSlots->groupBy(fn ($row) => (string) ($row['slot']['category_name'] ?? 'Choice / Elective')) as $categoryName => $categoryRows) {
            $packageCandidates = $this->sourcePackageCandidates($categoryRows);

            if ($packageCandidates->isNotEmpty()) {
                $matchingPackage = $packageCandidates->first(function ($package) use ($categoryRows, $submittedChoices) {
                    foreach ($categoryRows as $row) {
                        $slotId = (int) $row['slot']['id'];
                        $expected = collect($package['by_slot'][$slotId] ?? [])->sort()->values()->all();
                        $submitted = collect($submittedChoices->get($slotId, []))->sort()->values()->all();
                        if ($expected !== $submitted) {
                            return false;
                        }
                    }
                    return true;
                });

                if (! $matchingPackage) {
                    throw ValidationException::withMessages([
                        'academic_preference.course_choices' => "Select one complete {$categoryName} academic option. Its linked curriculum papers will be attached automatically.",
                    ]);
                }

                foreach ($categoryRows as $row) {
                    $slot = $row['slot'];
                    $term = $row['term'];
                    $slotId = (int) $slot['id'];
                    $processedSlotIds[] = $slotId;
                    $mappingIds = collect($matchingPackage['by_slot'][$slotId] ?? []);
                    foreach ($row['mappings']->whereIn('id', $mappingIds) as $mapping) {
                        $resolvedCourses[] = $this->courseRow($term, $slot, $mapping, 'APPLICANT_CHOICE');
                    }
                }
            }
        }

        // Categories that are not a complete source-discipline package remain genuine
        // course-level choices. Validation still follows each Curriculum Slot's min/max.
        foreach ($choiceSlots as $row) {
            $term = $row['term'];
            $slot = $row['slot'];
            $applicable = $row['mappings'];
            $slotId = (int) $slot['id'];

            if (in_array($slotId, $processedSlotIds, true)) {
                continue;
            }

            $selectedIds = collect($submittedChoices->get($slotId, []));
            $allowedIds = $applicable->pluck('id')->map(fn ($id) => (int) $id);
            if ($selectedIds->diff($allowedIds)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    "academic_preference.course_choices.{$slotId}" => 'One selected Choice Subject is not available for this Discipline / Specialization.',
                ]);
            }

            $min = (int) ($slot['min_selection'] ?? 1);
            $max = (int) ($slot['max_selection'] ?? $min);
            if ($selectedIds->count() < $min || $selectedIds->count() > $max) {
                throw ValidationException::withMessages([
                    "academic_preference.course_choices.{$slotId}" => "Select between {$min} and {$max} subject(s) for ".($slot['category_name'] ?? $slot['name']).'.',
                ]);
            }

            foreach ($applicable->whereIn('id', $selectedIds) as $mapping) {
                $resolvedCourses[] = $this->courseRow($term, $slot, $mapping, 'APPLICANT_CHOICE');
            }
        }

        return [
            'college_program_offering_id' => (int) $cycle->college_program_offering_id,
            'curriculum_id' => (int) ($options['curriculum']['id'] ?? 0),
            'discipline_id' => $disciplineId ?: null,
            'specialization_id' => $specializationId,
            'snapshot' => $options,
            'courses' => $resolvedCourses,
        ];
    }

    /**
     * Return source-discipline packages only when each source can fully satisfy every
     * Choice slot in the category with an exact configured selection count.
     *
     * Example: Minor -> History has one fixed History paper in every relevant term.
     * The applicant selects History once; all those papers are linked internally.
     * If a History slot offers three papers but only one must be chosen, this method
     * returns no package and the individual papers remain visible for selection.
     */
    private function sourcePackageCandidates($categoryRows)
    {
        $allSourceKeys = $categoryRows
            ->flatMap(fn ($row) => $row['mappings']->map(fn ($mapping) => $this->sourceKey($mapping)))
            ->unique()
            ->values();

        if ($allSourceKeys->isEmpty()) {
            return collect();
        }

        $candidates = collect();
        foreach ($allSourceKeys as $sourceKey) {
            $bySlot = [];
            $valid = true;

            foreach ($categoryRows as $row) {
                $slot = $row['slot'];
                $min = (int) ($slot['min_selection'] ?? 1);
                $max = (int) ($slot['max_selection'] ?? $min);
                if ($min !== $max) {
                    $valid = false;
                    break;
                }

                $mappingIds = $row['mappings']
                    ->filter(fn ($mapping) => $this->sourceKey($mapping) === $sourceKey)
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->sort()
                    ->values()
                    ->all();

                if (count($mappingIds) !== $min || count($mappingIds) === 0) {
                    $valid = false;
                    break;
                }

                $bySlot[(int) $slot['id']] = $mappingIds;
            }

            if ($valid) {
                $sample = $categoryRows->flatMap(fn ($row) => $row['mappings'])
                    ->first(fn ($mapping) => $this->sourceKey($mapping) === $sourceKey);
                $candidates->push([
                    'source_key' => $sourceKey,
                    'source_name' => $sample['source_discipline_name'] ?? 'Common / Interdisciplinary',
                    'by_slot' => $bySlot,
                ]);
            }
        }

        // Package mode is safe only when every offered source in the category can be
        // represented as a complete package. Otherwise keep granular course choices.
        return $candidates->count() === $allSourceKeys->count() ? $candidates : collect();
    }

    private function sourceKey(array $mapping): string
    {
        return $mapping['source_discipline_id']
            ? 'discipline:'.(int) $mapping['source_discipline_id']
            : 'common';
    }

    public function persist(CollegeAdmissionApplication $application, array $resolved): void
    {
        DB::table('college_admission_application_course_choices')->where('college_admission_application_id', $application->id)->delete();
        DB::table('college_admission_application_academic_preferences')->where('college_admission_application_id', $application->id)->delete();

        DB::table('college_admission_application_academic_preferences')->insert([
            'college_admission_application_id' => $application->id,
            'college_program_offering_id' => $resolved['college_program_offering_id'],
            'curriculum_id' => $resolved['curriculum_id'],
            'discipline_id' => $resolved['discipline_id'],
            'specialization_id' => $resolved['specialization_id'],
            'curriculum_snapshot' => json_encode($resolved['snapshot'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach ($resolved['courses'] as $course) {
            DB::table('college_admission_application_course_choices')->insert([
                'college_admission_application_id' => $application->id,
                'curriculum_term_id' => $course['curriculum_term_id'],
                'curriculum_slot_id' => $course['curriculum_slot_id'],
                'curriculum_course_mapping_id' => $course['curriculum_course_mapping_id'],
                'course_id' => $course['course_id'],
                'selection_source' => $course['selection_source'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    private function courseRow(array $term, array $slot, array $mapping, string $source): array
    {
        return [
            'curriculum_term_id' => (int) $term['id'],
            'curriculum_slot_id' => (int) $slot['id'],
            'curriculum_course_mapping_id' => (int) $mapping['id'],
            'course_id' => (int) $mapping['course_id'],
            'selection_source' => $source,
        ];
    }
}
