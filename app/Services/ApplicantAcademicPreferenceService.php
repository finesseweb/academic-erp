<?php

namespace App\Services;

use App\Models\CollegeAdmissionApplication;
use App\Models\CollegeAdmissionCycle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApplicantAcademicPreferenceService
{
    public function options(CollegeAdmissionCycle $cycle): array
    {
        $cycle->loadMissing(['programOffering.curriculum', 'programOffering.programTemplate']);
        $offering = $cycle->programOffering;
        if (! $offering || ! $offering->curriculum || ! $offering->programTemplate) {
            return ['disciplines' => [], 'terms' => [], 'curriculum' => null];
        }

        $disciplines = DB::table('program_template_disciplines as ptd')
            ->join('academic_disciplines as d', 'd.id', '=', 'ptd.discipline_id')
            ->where('ptd.program_template_id', $offering->program_template_id)
            ->where('d.status', 'ACTIVE')
            ->where('d.kind', 'DISCIPLINE')
            ->orderBy('d.display_order')->orderBy('d.name')
            ->get(['ptd.id as mapping_id', 'd.id', 'd.name', 'd.code'])
            ->map(function ($discipline) {
                $specializations = DB::table('program_template_discipline_specializations as ptds')
                    ->join('academic_disciplines as s', 's.id', '=', 'ptds.specialization_id')
                    ->where('ptds.program_template_discipline_id', $discipline->mapping_id)
                    ->where('s.status', 'ACTIVE')
                    ->where('s.kind', 'SPECIALIZATION')
                    ->orderBy('s.display_order')->orderBy('s.name')
                    ->get(['s.id', 's.name', 's.code'])
                    ->map(fn ($row) => (array) $row)->values()->all();
                return [
                    'id' => (int) $discipline->id,
                    'name' => $discipline->name,
                    'code' => $discipline->code,
                    'specializations' => $specializations,
                ];
            })->values()->all();

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
                            ->where('mapping.curriculum_slot_id', $slot->id)
                            ->where('mapping.status', 'ACTIVE')
                            ->where('course.status', 'ACTIVE')
                            ->orderByRaw('COALESCE(mapping.display_order, 65535)')->orderBy('mapping.id')
                            ->get([
                                'mapping.id', 'mapping.course_id', 'mapping.discipline_id', 'mapping.specialization_id',
                                'course.name as course_name', 'course.code as course_code',
                            ])->map(fn ($row) => [
                                'id' => (int) $row->id,
                                'course_id' => (int) $row->course_id,
                                'discipline_id' => $row->discipline_id ? (int) $row->discipline_id : null,
                                'specialization_id' => $row->specialization_id ? (int) $row->specialization_id : null,
                                'course_name' => $row->course_name,
                                'course_code' => $row->course_code,
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
        $disciplines = collect($options['disciplines']);
        $disciplineId = (int) ($input['discipline_id'] ?? 0);
        $discipline = $disciplines->firstWhere('id', $disciplineId);
        if ($disciplines->isNotEmpty() && ! $discipline) {
            throw ValidationException::withMessages(['academic_preference.discipline_id' => 'Select a Discipline available in this Program Offering.']);
        }

        $specializationId = filled($input['specialization_id'] ?? null) ? (int) $input['specialization_id'] : null;
        $specializations = collect($discipline['specializations'] ?? []);
        if ($specializations->isNotEmpty() && ! $specializationId) {
            throw ValidationException::withMessages(['academic_preference.specialization_id' => 'Select a Specialization for the selected Discipline.']);
        }
        if ($specializationId && ! $specializations->firstWhere('id', $specializationId)) {
            throw ValidationException::withMessages(['academic_preference.specialization_id' => 'Select a Specialization available under the selected Discipline.']);
        }

        $submittedChoices = collect($input['course_choices'] ?? [])->mapWithKeys(fn ($ids, $slotId) => [(int)$slotId => collect((array)$ids)->map(fn ($id)=>(int)$id)->filter()->unique()->values()->all()]);
        $resolvedCourses = [];

        foreach ($options['terms'] as $term) {
            foreach ($term['slots'] as $slot) {
                $applicable = collect($slot['mappings'])->filter(function ($mapping) use ($disciplineId, $specializationId) {
                    if ($mapping['discipline_id'] === null) return $mapping['specialization_id'] === null;
                    if ((int)$mapping['discipline_id'] !== $disciplineId) return false;
                    if ($mapping['specialization_id'] === null) return true;
                    return $specializationId !== null && (int)$mapping['specialization_id'] === $specializationId;
                })->values();

                if ($slot['selection_mode'] === 'MANDATORY') {
                    foreach ($applicable as $mapping) {
                        $resolvedCourses[] = $this->courseRow($term, $slot, $mapping, 'AUTO_MANDATORY');
                    }
                    continue;
                }

                if ($slot['selection_mode'] !== 'CHOICE' || $applicable->isEmpty()) continue;
                $selectedIds = collect($submittedChoices->get((int)$slot['id'], []));
                $allowedIds = $applicable->pluck('id')->map(fn ($id)=>(int)$id);
                if ($selectedIds->diff($allowedIds)->isNotEmpty()) {
                    throw ValidationException::withMessages(["academic_preference.course_choices.{$slot['id']}" => 'One selected Choice Subject is not available for this Discipline / Specialization.']);
                }
                $min = (int) ($slot['min_selection'] ?? 1);
                $max = (int) ($slot['max_selection'] ?? $min);
                if ($selectedIds->count() < $min || $selectedIds->count() > $max) {
                    throw ValidationException::withMessages(["academic_preference.course_choices.{$slot['id']}" => "Select between {$min} and {$max} subject(s) for {$slot['name']}."]);
                }
                foreach ($applicable->whereIn('id', $selectedIds) as $mapping) {
                    $resolvedCourses[] = $this->courseRow($term, $slot, $mapping, 'APPLICANT_CHOICE');
                }
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
