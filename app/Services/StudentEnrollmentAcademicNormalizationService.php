<?php

namespace App\Services;

use App\Models\StudentEnrollment;
use Illuminate\Support\Facades\DB;
use Throwable;

class StudentEnrollmentAcademicNormalizationService
{
    public function __construct(private readonly StudentEnrollmentAcademicContextService $academicContext) {}

    public function scan(?int $collegeId = null, ?int $enrollmentId = null, bool $apply = false): array
    {
        $query = StudentEnrollment::query()
            ->where('source_type', 'ADMISSION')
            ->with(['admission.application.academicPreference', 'admission.application.courseChoices']);

        if ($collegeId) $query->where('college_id', $collegeId);
        if ($enrollmentId) $query->whereKey($enrollmentId);

        $report = ['scanned'=>0, 'already_canonical'=>0, 'ready'=>0, 'normalized'=>0, 'needs_review'=>0, 'rows'=>[]];

        $query->orderBy('id')->chunkById(200, function ($enrollments) use (&$report, $apply) {
            foreach ($enrollments as $enrollment) {
                $report['scanned']++;
                $inspection = $this->inspect($enrollment);
                $report['rows'][] = $inspection;

                if ($inspection['status'] === 'ALREADY_CANONICAL') {
                    $report['already_canonical']++;
                    continue;
                }
                if ($inspection['status'] === 'NEEDS_REVIEW') {
                    $report['needs_review']++;
                    continue;
                }

                $report['ready']++;
                if (! $apply) continue;

                try {
                    DB::transaction(function () use ($enrollment) {
                        $locked = StudentEnrollment::query()->whereKey($enrollment->id)->lockForUpdate()->firstOrFail();
                        $locked->load(['admission.application.academicPreference', 'admission.application.courseChoices']);
                        $fresh = $this->inspect($locked);
                        if ($fresh['status'] === 'ALREADY_CANONICAL') return;
                        if ($fresh['status'] !== 'READY') throw new \RuntimeException(implode(' ', $fresh['issues']));

                        $application = $locked->admission->application;
                        $preference = $application->academicPreference;
                        $this->academicContext->persist(
                            $locked,
                            (int) $preference->curriculum_id,
                            $preference->discipline_id ? (int) $preference->discipline_id : null,
                            $preference->specialization_id ? (int) $preference->specialization_id : null,
                            $application->courseChoices,
                        );

                        DB::table('audit_logs')->insert([
                            'actor_user_id' => null,
                            'event' => 'student.enrollment.academic_normalized',
                            'resource_type' => 'student_enrollment',
                            'resource_id' => $locked->id,
                            'scope_type' => 'COLLEGE',
                            'scope_reference' => 'college:'.$locked->college_id,
                            'before' => json_encode($fresh['before']),
                            'after' => json_encode([
                                'curriculum_id' => (int) $preference->curriculum_id,
                                'discipline_id' => $preference->discipline_id ? (int) $preference->discipline_id : null,
                                'specialization_id' => $preference->specialization_id ? (int) $preference->specialization_id : null,
                                'course_mapping_ids' => $application->courseChoices->pluck('curriculum_course_mapping_id')->map(fn ($v)=>(int)$v)->values()->all(),
                            ]),
                            'ip_address' => null,
                            'created_at' => now(),
                        ]);
                    });
                    $report['normalized']++;
                } catch (Throwable $e) {
                    $report['needs_review']++;
                    $report['rows'][] = ['enrollment_id'=>$enrollment->id, 'status'=>'NEEDS_REVIEW', 'issues'=>[$e->getMessage()]];
                }
            }
        });

        return $report;
    }

    private function inspect(StudentEnrollment $enrollment): array
    {
        $issues = [];
        $admission = $enrollment->admission;
        $application = $admission?->application;
        $preference = $application?->academicPreference;
        $choices = $application?->courseChoices;

        if (! $admission) $issues[] = 'Admission provenance is missing.';
        if (! $application) $issues[] = 'Admission Application provenance is missing.';
        if (! $preference) $issues[] = 'Application Academic Preference is missing.';
        if ($issues) return ['enrollment_id'=>$enrollment->id, 'status'=>'NEEDS_REVIEW', 'issues'=>$issues];

        if ((int) $preference->college_program_offering_id !== (int) $enrollment->college_program_offering_id) {
            $issues[] = 'Enrollment Programme Offering conflicts with the Application Academic Preference.';
        }

        foreach ([
            'curriculum_id' => $preference->curriculum_id,
            'discipline_id' => $preference->discipline_id,
            'specialization_id' => $preference->specialization_id,
        ] as $column => $expected) {
            $actual = $enrollment->{$column};
            if ($actual !== null && $expected !== null && (int) $actual !== (int) $expected) {
                $issues[] = "{$column} conflicts with the authoritative Application Academic Preference.";
            }
        }

        $existing = DB::table('student_enrollment_course_choices')->where('student_enrollment_id', $enrollment->id)->get();
        $expectedByMapping = $choices->keyBy(fn ($row)=>(int)$row->curriculum_course_mapping_id);
        foreach ($existing as $row) {
            $expected = $expectedByMapping->get((int) $row->curriculum_course_mapping_id);
            if (! $expected) {
                $issues[] = "Enrollment has course mapping {$row->curriculum_course_mapping_id} not present in the authoritative Application choices.";
                continue;
            }
            foreach (['curriculum_term_id','curriculum_slot_id','course_id','selection_source'] as $column) {
                if ((string)$row->{$column} !== (string)$expected->{$column}) {
                    $issues[] = "Saved course mapping {$row->curriculum_course_mapping_id} conflicts on {$column}.";
                }
            }
        }
        if ($issues) return ['enrollment_id'=>$enrollment->id, 'status'=>'NEEDS_REVIEW', 'issues'=>$issues];

        $expectedIds = $choices->pluck('curriculum_course_mapping_id')->map(fn($v)=>(int)$v)->sort()->values()->all();
        $existingIds = $existing->pluck('curriculum_course_mapping_id')->map(fn($v)=>(int)$v)->sort()->values()->all();
        $contextComplete = $enrollment->curriculum_id !== null
            && ($preference->discipline_id === null || $enrollment->discipline_id !== null)
            && ($preference->specialization_id === null || $enrollment->specialization_id !== null);

        $before = [
            'curriculum_id'=>$enrollment->curriculum_id,
            'discipline_id'=>$enrollment->discipline_id,
            'specialization_id'=>$enrollment->specialization_id,
            'course_mapping_ids'=>$existingIds,
        ];

        if ($contextComplete && $existingIds === $expectedIds) {
            return ['enrollment_id'=>$enrollment->id, 'status'=>'ALREADY_CANONICAL', 'issues'=>[], 'before'=>$before];
        }

        return ['enrollment_id'=>$enrollment->id, 'status'=>'READY', 'issues'=>[], 'before'=>$before, 'expected'=>[
            'curriculum_id'=>(int)$preference->curriculum_id,
            'discipline_id'=>$preference->discipline_id ? (int)$preference->discipline_id : null,
            'specialization_id'=>$preference->specialization_id ? (int)$preference->specialization_id : null,
            'course_mapping_ids'=>$expectedIds,
        ]];
    }
}
