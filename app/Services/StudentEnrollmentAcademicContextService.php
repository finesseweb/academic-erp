<?php

namespace App\Services;

use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentEnrollmentAcademicContextService
{
    /**
     * Persist the canonical post-entry academic context for an Enrollment.
     * Admission and Import must both call this writer so downstream modules
     * never need provenance-specific academic logic.
     */
    public function persist(
        StudentEnrollment $enrollment,
        ?int $curriculumId,
        ?int $disciplineId,
        ?int $specializationId,
        iterable $courses,
    ): void {
        $this->assertCompatible('curriculum_id', $enrollment->curriculum_id, $curriculumId);
        $this->assertCompatible('discipline_id', $enrollment->discipline_id, $disciplineId);
        $this->assertCompatible('specialization_id', $enrollment->specialization_id, $specializationId);

        $enrollment->forceFill([
            'curriculum_id' => $enrollment->curriculum_id ?? $curriculumId,
            'discipline_id' => $enrollment->discipline_id ?? $disciplineId,
            'specialization_id' => $enrollment->specialization_id ?? $specializationId,
        ])->save();

        foreach ($courses as $course) {
            $row = $this->courseRow($course);
            $existing = DB::table('student_enrollment_course_choices')
                ->where('student_enrollment_id', $enrollment->id)
                ->where('curriculum_course_mapping_id', $row['curriculum_course_mapping_id'])
                ->first();

            if ($existing) {
                foreach (['curriculum_term_id', 'curriculum_slot_id', 'course_id', 'selection_source'] as $column) {
                    if ((string) $existing->{$column} !== (string) $row[$column]) {
                        throw ValidationException::withMessages([
                            'academic_context' => "Enrollment {$enrollment->id} has a conflicting saved academic choice for mapping {$row['curriculum_course_mapping_id']}.",
                        ]);
                    }
                }
                continue;
            }

            DB::table('student_enrollment_course_choices')->insert($row + [
                'student_enrollment_id' => $enrollment->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function assertCompatible(string $column, mixed $existing, mixed $resolved): void
    {
        if ($existing !== null && $resolved !== null && (int) $existing !== (int) $resolved) {
            throw ValidationException::withMessages([
                'academic_context' => "Existing {$column} conflicts with the authoritative source record; automatic normalization was not applied.",
            ]);
        }
    }

    private function courseRow(mixed $course): array
    {
        $get = fn (string $key) => is_array($course) ? ($course[$key] ?? null) : ($course->{$key} ?? null);
        return [
            'curriculum_term_id' => (int) $get('curriculum_term_id'),
            'curriculum_slot_id' => (int) $get('curriculum_slot_id'),
            'curriculum_course_mapping_id' => (int) $get('curriculum_course_mapping_id'),
            'course_id' => (int) $get('course_id'),
            'selection_source' => (string) $get('selection_source'),
        ];
    }
}
