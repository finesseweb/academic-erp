<?php

namespace App\Services;

use App\Models\Curriculum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CurriculumStructureValidationService
{
    public function validate(Curriculum $curriculum): array
    {
        $issues = collect();

        $terms = DB::table('curriculum_terms')
            ->where('curriculum_id', $curriculum->id)
            ->orderBy('sequence_no')
            ->orderBy('id')
            ->get();

        if ($terms->isEmpty()) {
            $issues->push($this->issue(
                'ERROR',
                'CURRICULUM_NO_TERMS',
                'Curriculum has no Terms / Semesters.'
            ));
        } else {
            $this->validateTermSequence($terms, $issues);
        }

        foreach ($terms as $term) {
            if ($term->status !== 'ACTIVE') {
                continue;
            }

            $slots = DB::table('curriculum_slots')
                ->where('curriculum_term_id', $term->id)
                ->where('status', 'ACTIVE')
                ->orderBy('display_order')
                ->orderBy('id')
                ->get();

            if ($slots->isEmpty()) {
                $issues->push($this->issue(
                    'ERROR',
                    'TERM_NO_ACTIVE_SLOTS',
                    "{$term->name} has no active Curriculum Slots.",
                    'TERM',
                    (int) $term->id
                ));
                continue;
            }

            foreach ($slots as $slot) {
                $this->validateSlot(
                    $curriculum,
                    $term,
                    $slot,
                    $issues
                );
            }
        }

        $errors = $issues->where('severity', 'ERROR')->count();
        $warnings = $issues->where('severity', 'WARNING')->count();

        return [
            'valid' => $errors === 0,
            'errors' => $errors,
            'warnings' => $warnings,
            'issues' => $issues->values()->all(),
        ];
    }

    private function validateTermSequence(
        Collection $terms,
        Collection $issues
    ): void {
        $sequence = $terms
            ->pluck('sequence_no')
            ->map(fn ($value) => (int) $value)
            ->values();

        $expected = collect(range(1, $terms->count()));

        $valid = $sequence->count() === $expected->count()
            && $sequence->every(
                fn ($value, $index) =>
                    $value === $expected[$index]
            );

        if (! $valid) {
            $issues->push($this->issue(
                'ERROR',
                'TERM_SEQUENCE_INVALID',
                'Term / Semester sequence must be continuous from 1.'
            ));
        }
    }

    private function validateSlot(
        Curriculum $curriculum,
        object $term,
        object $slot,
        Collection $issues
    ): void {
        $allMappings = DB::table(
            'curriculum_course_mappings as mapping'
        )
            ->join(
                'courses',
                'courses.id',
                '=',
                'mapping.course_id'
            )
            ->leftJoin(
                'academic_disciplines as discipline',
                'discipline.id',
                '=',
                'mapping.discipline_id'
            )
            ->leftJoin(
                'academic_disciplines as specialization',
                'specialization.id',
                '=',
                'mapping.specialization_id'
            )
            ->where('mapping.curriculum_slot_id', $slot->id)
            ->orderByRaw(
                'COALESCE(mapping.display_order, 65535)'
            )
            ->orderBy('mapping.id')
            ->get([
                'mapping.id',
                'mapping.course_id',
                'mapping.discipline_id',
                'mapping.specialization_id',
                'mapping.display_order',
                'mapping.status as mapping_status',
                'courses.name as course_name',
                'courses.status as course_status',
                'courses.university_id as course_university_id',
                'courses.course_category_id',
                'courses.course_type_id',
                'discipline.name as discipline_name',
                'discipline.status as discipline_status',
                'discipline.kind as discipline_kind',
                'discipline.university_id as discipline_university_id',
                'specialization.name as specialization_name',
                'specialization.status as specialization_status',
                'specialization.kind as specialization_kind',
                'specialization.parent_id as specialization_parent_id',
                'specialization.university_id as specialization_university_id',
            ]);

        $activeMappings = $allMappings
            ->where('mapping_status', 'ACTIVE')
            ->values();

        if ($activeMappings->isEmpty()) {
            $issues->push($this->issue(
                'ERROR',
                'SLOT_NO_ACTIVE_COURSE_MAPPING',
                "{$term->name} / {$slot->name} has no active Course / Paper Mapping.",
                'SLOT',
                (int) $slot->id
            ));

            return;
        }


        if ($slot->credits === null) {
            $issues->push($this->issue(
                'ERROR',
                'SLOT_CREDITS_MISSING',
                "{$term->name} / {$slot->name} has no Credits. Edit the Slot and assign Credits.",
                'SLOT',
                (int) $slot->id
            ));
        } elseif (! is_numeric($slot->credits) || (float) $slot->credits < 0) {
            $issues->push($this->issue(
                'ERROR',
                'SLOT_CREDITS_INVALID',
                "{$term->name} / {$slot->name} has an invalid Credit value.",
                'SLOT',
                (int) $slot->id
            ));
        }

        $selectionMode = strtoupper(
            (string) $slot->selection_mode
        );

        if ($selectionMode === 'CHOICE') {
            $min = $slot->min_selection === null
                ? null
                : (int) $slot->min_selection;

            $max = $slot->max_selection === null
                ? null
                : (int) $slot->max_selection;

            if ($min === null || $max === null) {
                $issues->push($this->issue(
                    'ERROR',
                    'CHOICE_SELECTION_RULE_MISSING',
                    "{$term->name} / {$slot->name} must define Minimum and Maximum Selection.",
                    'SLOT',
                    (int) $slot->id
                ));
            } elseif ($min < 1 || $max < $min) {
                $issues->push($this->issue(
                    'ERROR',
                    'CHOICE_SELECTION_RULE_INVALID',
                    "{$term->name} / {$slot->name} has invalid Minimum / Maximum Selection.",
                    'SLOT',
                    (int) $slot->id
                ));
            } elseif ($activeMappings->count() < $max) {
                $issues->push($this->issue(
                    'ERROR',
                    'CHOICE_NOT_ENOUGH_MAPPINGS',
                    "{$term->name} / {$slot->name} allows maximum {$max} selection(s), but only {$activeMappings->count()} active Course / Paper Mapping(s) exist.",
                    'SLOT',
                    (int) $slot->id
                ));
            }
        }

        $this->validateMappingDisplayOrder(
            $term,
            $slot,
            $allMappings,
            $issues
        );

        foreach ($activeMappings as $mapping) {
            $this->validateMapping(
                $curriculum,
                $term,
                $slot,
                $mapping,
                $issues
            );
        }
    }

    private function validateMappingDisplayOrder(
        object $term,
        object $slot,
        Collection $mappings,
        Collection $issues
    ): void {
        if ($mappings->isEmpty()) {
            return;
        }

        $orders = $mappings
            ->pluck('display_order')
            ->map(fn ($value) => (int) $value)
            ->values();

        $expected = collect(range(1, $mappings->count()));

        $valid = $orders->count() === $expected->count()
            && $orders->every(
                fn ($value, $index) =>
                    $value === $expected[$index]
            );

        if (! $valid) {
            $issues->push($this->issue(
                'ERROR',
                'COURSE_MAPPING_ORDER_INVALID',
                "{$term->name} / {$slot->name} has non-continuous Course Mapping display order.",
                'SLOT',
                (int) $slot->id
            ));
        }
    }

    private function validateMapping(
        Curriculum $curriculum,
        object $term,
        object $slot,
        object $mapping,
        Collection $issues
    ): void {
        $prefix = "{$term->name} / {$slot->name} / {$mapping->course_name}";

        if (
            $mapping->course_status !== 'ACTIVE'
            || (int) $mapping->course_university_id
                !== (int) $curriculum->university_id
            || (int) $mapping->course_category_id
                !== (int) $slot->course_category_id
            || (int) $mapping->course_type_id
                !== (int) $slot->course_type_id
        ) {
            $issues->push($this->issue(
                'ERROR',
                'COURSE_MAPPING_INCOMPATIBLE',
                "{$prefix} no longer matches the Slot's active University / Category / Type rules.",
                'MAPPING',
                (int) $mapping->id
            ));
        }

        if ($mapping->discipline_id === null) {
            $issues->push($this->issue(
                'ERROR',
                'MAPPING_DISCIPLINE_MISSING',
                "{$prefix} has no Discipline. Edit this mapping and select a Program Template Discipline.",
                'MAPPING',
                (int) $mapping->id
            ));

            return;
        }

        $templateDiscipline = DB::table(
            'program_template_disciplines'
        )
            ->where(
                'program_template_id',
                $curriculum->program_template_id
            )
            ->where(
                'discipline_id',
                $mapping->discipline_id
            )
            ->first(['id']);

        if (
            ! $templateDiscipline
            || $mapping->discipline_status !== 'ACTIVE'
            || $mapping->discipline_kind !== 'DISCIPLINE'
            || (int) $mapping->discipline_university_id
                !== (int) $curriculum->university_id
        ) {
            $issues->push($this->issue(
                'ERROR',
                'MAPPING_DISCIPLINE_INVALID',
                "{$prefix} uses a Discipline that is not active or allowed in this Program Template.",
                'MAPPING',
                (int) $mapping->id
            ));

            return;
        }

        if ($mapping->specialization_id === null) {
            return;
        }

        $specializationAllowed = DB::table(
            'program_template_discipline_specializations'
        )
            ->where(
                'program_template_discipline_id',
                $templateDiscipline->id
            )
            ->where(
                'specialization_id',
                $mapping->specialization_id
            )
            ->exists();

        if (
            ! $specializationAllowed
            || $mapping->specialization_status !== 'ACTIVE'
            || $mapping->specialization_kind !== 'SPECIALIZATION'
            || (int) $mapping->specialization_parent_id
                !== (int) $mapping->discipline_id
            || (int) $mapping->specialization_university_id
                !== (int) $curriculum->university_id
        ) {
            $issues->push($this->issue(
                'ERROR',
                'MAPPING_SPECIALIZATION_INVALID',
                "{$prefix} uses a Specialization that is not allowed under the selected Program Template Discipline.",
                'MAPPING',
                (int) $mapping->id
            ));
        }
    }

    private function issue(
        string $severity,
        string $code,
        string $message,
        ?string $scopeType = null,
        ?int $scopeId = null
    ): array {
        return compact(
            'severity',
            'code',
            'message',
            'scopeType',
            'scopeId'
        );
    }
}
