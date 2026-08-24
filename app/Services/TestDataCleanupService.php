<?php

namespace App\Services;

use App\Models\AcademicPolicy;
use App\Models\Curriculum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class TestDataCleanupService
{
    private const CURRICULUM_DOWNSTREAM_REFERENCES = [
        ['course_offerings', 'curriculum_id'],
        ['course_offerings', 'curriculum_version_id'],
        ['curriculum_assignments', 'curriculum_id'],
        ['student_curriculum_assignments', 'curriculum_id'],
        ['student_enrollments', 'curriculum_id'],
        ['students', 'curriculum_id'],
        ['admissions', 'curriculum_id'],
        ['admission_applications', 'curriculum_id'],
        ['academic_policies', 'curriculum_id'],
        ['academic_policy_progression_rule_sets', 'curriculum_id'],
    ];

    private const ACADEMIC_POLICY_DOWNSTREAM_REFERENCES = [
        ['course_offerings', 'academic_policy_id'],
        ['student_academic_records', 'academic_policy_id'],
        ['student_academic_statuses', 'academic_policy_id'],
        ['student_results', 'academic_policy_id'],
        ['results', 'academic_policy_id'],
        ['semester_results', 'academic_policy_id'],
        ['grade_cards', 'academic_policy_id'],
        ['transcripts', 'academic_policy_id'],
        ['student_progression_decisions', 'academic_policy_id'],
        ['student_attendance_eligibilities', 'academic_policy_id'],
    ];

    public function curriculumCleanupPreview(Curriculum $curriculum): array
    {
        $termIds = DB::table('curriculum_terms')
            ->where('curriculum_id', $curriculum->id)
            ->pluck('id');

        $slotIds = DB::table('curriculum_slots')
            ->whereIn('curriculum_term_id', $termIds)
            ->pluck('id');

        $approvalRequestIds = Schema::hasTable('approval_requests')
            ? DB::table('approval_requests')
                ->where('subject_type', 'CURRICULUM')
                ->where('subject_id', $curriculum->id)
                ->pluck('id')
            : collect();

        return [
            'id' => $curriculum->id,
            'code' => $curriculum->code,
            'name' => $curriculum->name,
            'version' => $curriculum->version,
            'lifecycle_status' => $curriculum->lifecycle_status,
            'approval_status' =>
                $curriculum->approval_status ?? 'NOT_SUBMITTED',
            'terms' => $termIds->count(),
            'slots' => $slotIds->count(),
            'course_mappings' => DB::table('curriculum_course_mappings')
                ->whereIn('curriculum_slot_id', $slotIds)
                ->count(),
            'approval_requests' => $approvalRequestIds->count(),
            'approval_request_stages' =>
                Schema::hasTable('approval_request_stages')
                    ? DB::table('approval_request_stages')
                        ->whereIn('approval_request_id', $approvalRequestIds)
                        ->count()
                    : 0,
            'downstream_references' =>
                $this->downstreamReferences(
                    $curriculum->id,
                    self::CURRICULUM_DOWNSTREAM_REFERENCES
                ),
        ];
    }

    public function resetCurriculumApproval(
        Curriculum $curriculum,
        int $actorId
    ): array {
        $this->assertCleanupEnabled();

        $preview = $this->curriculumCleanupPreview($curriculum);

        if (count($preview['downstream_references']) > 0) {
            throw ValidationException::withMessages([
                'curriculum' =>
                    'This Curriculum already has downstream operational references. Approval reset is blocked.',
            ]);
        }

        return DB::transaction(function () use (
            $curriculum,
            $preview,
            $actorId
        ) {
            $requestIds = Schema::hasTable('approval_requests')
                ? DB::table('approval_requests')
                    ->where('subject_type', 'CURRICULUM')
                    ->where('subject_id', $curriculum->id)
                    ->pluck('id')
                : collect();

            if (
                Schema::hasTable('approval_request_stages') &&
                $requestIds->isNotEmpty()
            ) {
                DB::table('approval_request_stages')
                    ->whereIn('approval_request_id', $requestIds)
                    ->delete();
            }

            if (
                Schema::hasTable('approval_requests') &&
                $requestIds->isNotEmpty()
            ) {
                DB::table('approval_requests')
                    ->whereIn('id', $requestIds)
                    ->delete();
            }

            DB::table('curricula')
                ->where('id', $curriculum->id)
                ->update([
                    'lifecycle_status' => 'DRAFT',
                    'approval_status' => 'NOT_SUBMITTED',
                    'structure_validation_hash' => null,
                    'structure_validated_at' => null,
                    'structure_validated_by' => null,
                    'updated_at' => now(),
                    'updated_by' => $actorId,
                ]);

            $this->audit(
                'TEST_CURRICULUM_APPROVAL_RESET',
                'test_data_cleanup',
                $curriculum->id,
                [
                    'curriculum' => $curriculum->toArray(),
                    'removed_approval_requests' =>
                        $preview['approval_requests'],
                    'removed_approval_request_stages' =>
                        $preview['approval_request_stages'],
                ],
                $actorId
            );

            return $preview;
        });
    }

    public function cleanupCurriculum(
        Curriculum $curriculum,
        int $actorId
    ): array {
        $this->assertCleanupEnabled();

        $preview = $this->curriculumCleanupPreview($curriculum);

        if (count($preview['downstream_references']) > 0) {
            throw ValidationException::withMessages([
                'curriculum' =>
                    'This Curriculum is already referenced by operational data and cannot be removed by Test Data Cleanup.',
            ]);
        }

        return DB::transaction(function () use (
            $curriculum,
            $preview,
            $actorId
        ) {
            $curriculumId = $curriculum->id;

            $termIds = DB::table('curriculum_terms')
                ->where('curriculum_id', $curriculumId)
                ->pluck('id');

            $slotIds = DB::table('curriculum_slots')
                ->whereIn('curriculum_term_id', $termIds)
                ->pluck('id');

            $requestIds = Schema::hasTable('approval_requests')
                ? DB::table('approval_requests')
                    ->where('subject_type', 'CURRICULUM')
                    ->where('subject_id', $curriculumId)
                    ->pluck('id')
                : collect();

            if (
                Schema::hasTable('approval_request_stages') &&
                $requestIds->isNotEmpty()
            ) {
                DB::table('approval_request_stages')
                    ->whereIn('approval_request_id', $requestIds)
                    ->delete();
            }

            if (
                Schema::hasTable('approval_requests') &&
                $requestIds->isNotEmpty()
            ) {
                DB::table('approval_requests')
                    ->whereIn('id', $requestIds)
                    ->delete();
            }

            DB::table('curriculum_course_mappings')
                ->whereIn('curriculum_slot_id', $slotIds)
                ->delete();

            DB::table('curriculum_slots')
                ->whereIn('id', $slotIds)
                ->delete();

            DB::table('curriculum_terms')
                ->whereIn('id', $termIds)
                ->delete();

            $before = $curriculum->toArray();
            $curriculum->delete();

            $this->audit(
                'TEST_CURRICULUM_DATA_CLEANED',
                'test_data_cleanup',
                $curriculumId,
                [
                    'curriculum' => $before,
                    'deleted_counts' => $preview,
                ],
                $actorId
            );

            return $preview;
        });
    }


    public function academicPolicyCleanupPreview(
        AcademicPolicy $policy
    ): array {
        $chainIds = $this->academicPolicyChainIds($policy->id);

        $approvalRequestIds = Schema::hasTable('approval_requests')
            ? DB::table('approval_requests')
                ->where('subject_type', 'ACADEMIC_POLICY')
                ->whereIn('subject_id', $chainIds)
                ->pluck('id')
            : collect();

        $ruleSetIds = Schema::hasTable(
            'academic_policy_progression_rule_sets'
        )
            ? DB::table('academic_policy_progression_rule_sets')
                ->whereIn('academic_policy_id', $chainIds)
                ->pluck('id')
            : collect();

        $downstream = $this->downstreamReferencesForIds(
            $chainIds,
            self::ACADEMIC_POLICY_DOWNSTREAM_REFERENCES
        );

        return [
            'id' => $policy->id,
            'code' => $policy->code,
            'name' => $policy->name,
            'version' => $policy->version,
            'lifecycle_status' => $policy->lifecycle_status,
            'approval_status' =>
                $policy->approval_status ?? 'NOT_SUBMITTED',
            'is_current_version' =>
                (bool) ($policy->is_current_version ?? false),
            'scope_type' => $policy->scope_type,
            'chain_versions' => $chainIds->count(),
            'approval_requests' => $approvalRequestIds->count(),
            'approval_request_stages' =>
                Schema::hasTable('approval_request_stages')
                    ? DB::table('approval_request_stages')
                        ->whereIn(
                            'approval_request_id',
                            $approvalRequestIds
                        )
                        ->count()
                    : 0,
            'credit_completion_rules' =>
                $this->countWhereIn(
                    'academic_policy_credit_completion_rules',
                    'academic_policy_id',
                    $chainIds
                ),
            'credit_category_requirements' =>
                $this->countWhereIn(
                    'academic_policy_credit_category_requirements',
                    'academic_policy_id',
                    $chainIds
                ),
            'attendance_rules' =>
                $this->countWhereIn(
                    'academic_policy_attendance_rules',
                    'academic_policy_id',
                    $chainIds
                ),
            'assessment_exam_rules' =>
                $this->countWhereIn(
                    'academic_policy_assessment_exam_rules',
                    'academic_policy_id',
                    $chainIds
                ),
            'grading_rules' =>
                $this->countWhereIn(
                    'academic_policy_grading_rules',
                    'academic_policy_id',
                    $chainIds
                ),
            'grade_bands' =>
                $this->countWhereIn(
                    'academic_policy_grade_bands',
                    'academic_policy_id',
                    $chainIds
                ),
            'progression_rule_sets' => $ruleSetIds->count(),
            'progression_rule_terms' =>
                $this->countWhereIn(
                    'academic_policy_progression_rule_terms',
                    'progression_rule_set_id',
                    $ruleSetIds
                ),
            'downstream_references' => $downstream,
            'can_cleanup' => count($downstream) === 0,
            'can_reset_approval' =>
                count($downstream) === 0 &&
                $chainIds->count() === 1,
        ];
    }

    public function resetAcademicPolicyApproval(
        AcademicPolicy $policy,
        int $actorId
    ): array {
        $this->assertCleanupEnabled();

        $preview = $this->academicPolicyCleanupPreview($policy);

        if (count($preview['downstream_references']) > 0) {
            throw ValidationException::withMessages([
                'academic_policy' =>
                    'This Academic Policy already has downstream operational references. Approval reset is blocked.',
            ]);
        }

        if ((int) $preview['chain_versions'] > 1) {
            throw ValidationException::withMessages([
                'academic_policy' =>
                    'Approval reset is blocked for a version chain. Clean the complete test policy chain instead, or reset a standalone test policy.',
            ]);
        }

        return DB::transaction(function () use (
            $policy,
            $preview,
            $actorId
        ) {
            $requestIds = Schema::hasTable('approval_requests')
                ? DB::table('approval_requests')
                    ->where('subject_type', 'ACADEMIC_POLICY')
                    ->where('subject_id', $policy->id)
                    ->pluck('id')
                : collect();

            if (
                Schema::hasTable('approval_request_stages') &&
                $requestIds->isNotEmpty()
            ) {
                DB::table('approval_request_stages')
                    ->whereIn('approval_request_id', $requestIds)
                    ->delete();
            }

            if (
                Schema::hasTable('approval_requests') &&
                $requestIds->isNotEmpty()
            ) {
                DB::table('approval_requests')
                    ->whereIn('id', $requestIds)
                    ->delete();
            }

            DB::table('academic_policies')
                ->where('id', $policy->id)
                ->update([
                    'lifecycle_status' => 'DRAFT',
                    'approval_status' => 'NOT_SUBMITTED',
                    'is_current_version' => false,
                    'superseded_by_id' => null,
                    'validation_hash' => null,
                    'validated_at' => null,
                    'validated_by' => null,
                    'updated_by' => $actorId,
                    'updated_at' => now(),
                ]);

            $this->audit(
                'TEST_ACADEMIC_POLICY_APPROVAL_RESET',
                'test_data_cleanup',
                $policy->id,
                [
                    'policy' => $policy->toArray(),
                    'removed_approval_requests' =>
                        $preview['approval_requests'],
                    'removed_approval_request_stages' =>
                        $preview['approval_request_stages'],
                ],
                $actorId
            );

            return $preview;
        });
    }

    public function cleanupAcademicPolicy(
        AcademicPolicy $policy,
        int $actorId
    ): array {
        $this->assertCleanupEnabled();

        $preview = $this->academicPolicyCleanupPreview($policy);

        if (count($preview['downstream_references']) > 0) {
            throw ValidationException::withMessages([
                'academic_policy' =>
                    'This Academic Policy version chain is referenced by operational data and cannot be removed by Test Data Cleanup.',
            ]);
        }

        return DB::transaction(function () use (
            $policy,
            $preview,
            $actorId
        ) {
            $chainIds = $this->academicPolicyChainIds($policy->id);

            $requestIds = Schema::hasTable('approval_requests')
                ? DB::table('approval_requests')
                    ->where('subject_type', 'ACADEMIC_POLICY')
                    ->whereIn('subject_id', $chainIds)
                    ->pluck('id')
                : collect();

            if (
                Schema::hasTable('approval_request_stages') &&
                $requestIds->isNotEmpty()
            ) {
                DB::table('approval_request_stages')
                    ->whereIn('approval_request_id', $requestIds)
                    ->delete();
            }

            if (
                Schema::hasTable('approval_requests') &&
                $requestIds->isNotEmpty()
            ) {
                DB::table('approval_requests')
                    ->whereIn('id', $requestIds)
                    ->delete();
            }

            $ruleSetIds = Schema::hasTable(
                'academic_policy_progression_rule_sets'
            )
                ? DB::table(
                    'academic_policy_progression_rule_sets'
                )
                    ->whereIn('academic_policy_id', $chainIds)
                    ->pluck('id')
                : collect();

            if (
                Schema::hasTable(
                    'academic_policy_progression_rule_terms'
                ) &&
                $ruleSetIds->isNotEmpty()
            ) {
                DB::table(
                    'academic_policy_progression_rule_terms'
                )
                    ->whereIn(
                        'progression_rule_set_id',
                        $ruleSetIds
                    )
                    ->delete();
            }

            $this->deleteWhereIn(
                'academic_policy_progression_rule_sets',
                'academic_policy_id',
                $chainIds
            );
            $this->deleteWhereIn(
                'academic_policy_progression_rules',
                'academic_policy_id',
                $chainIds
            );
            $this->deleteWhereIn(
                'academic_policy_grade_bands',
                'academic_policy_id',
                $chainIds
            );
            $this->deleteWhereIn(
                'academic_policy_grading_rules',
                'academic_policy_id',
                $chainIds
            );
            $this->deleteWhereIn(
                'academic_policy_assessment_exam_rules',
                'academic_policy_id',
                $chainIds
            );
            $this->deleteWhereIn(
                'academic_policy_attendance_rules',
                'academic_policy_id',
                $chainIds
            );
            $this->deleteWhereIn(
                'academic_policy_credit_category_requirements',
                'academic_policy_id',
                $chainIds
            );
            $this->deleteWhereIn(
                'academic_policy_credit_completion_rules',
                'academic_policy_id',
                $chainIds
            );

            // Break the self-version links before deleting the complete
            // test chain so FK order cannot leave a circular dependency.
            DB::table('academic_policies')
                ->whereIn('id', $chainIds)
                ->update([
                    'parent_policy_id' => null,
                    'superseded_by_id' => null,
                    'updated_at' => now(),
                ]);

            $before = DB::table('academic_policies')
                ->whereIn('id', $chainIds)
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();

            DB::table('academic_policies')
                ->whereIn('id', $chainIds)
                ->delete();

            $this->audit(
                'TEST_ACADEMIC_POLICY_CHAIN_CLEANED',
                'test_data_cleanup',
                $policy->id,
                [
                    'policies' => $before,
                    'deleted_counts' => $preview,
                ],
                $actorId
            );

            return $preview;
        });
    }

    public function listMaintenanceEntities(
        int $universityId
    ): array {
        return [
            'courses' => $this->courseRows($universityId),
            'course_categories' =>
                $this->simpleRows(
                    'course_categories',
                    $universityId,
                    fn ($id) => [
                        'courses' => $this->countIfExists(
                            'courses',
                            'course_category_id',
                            $id
                        ),
                        'academic_policy_requirements' =>
                            $this->countIfExists(
                                'academic_policy_credit_category_requirements',
                                'course_category_id',
                                $id
                            ),
                    ]
                ),
            'course_types' =>
                $this->simpleRows(
                    'course_types',
                    $universityId,
                    fn ($id) => [
                        'courses' => $this->countIfExists(
                            'courses',
                            'course_type_id',
                            $id
                        ),
                    ]
                ),
            'program_templates' =>
                $this->simpleRows(
                    'program_templates',
                    $universityId,
                    fn ($id) => [
                        'curricula' => $this->countIfExists(
                            'curricula',
                            'program_template_id',
                            $id
                        ),
                        'disciplines' =>
                            $this->countIfExists(
                                'program_template_disciplines',
                                'program_template_id',
                                $id
                            ),
                        'academic_policies' =>
                            $this->countIfExists(
                                'academic_policies',
                                'program_template_id',
                                $id
                            ),
                    ]
                ),
            'disciplines' =>
                $this->simpleRows(
                    'academic_disciplines',
                    $universityId,
                    fn ($id) => [
                        'curriculum_mappings' =>
                            $this->disciplineMappingCount($id),
                        'program_templates' =>
                            $this->countIfExists(
                                'program_template_disciplines',
                                'discipline_id',
                                $id
                            ),
                    ]
                ),
            'academic_sessions' =>
                $this->simpleRows(
                    'academic_sessions',
                    $universityId,
                    fn ($id) => [
                        'curricula' => $this->countIfExists(
                            'curricula',
                            'academic_session_id',
                            $id
                        ),
                        'academic_policies' =>
                            $this->countIfExists(
                                'academic_policies',
                                'academic_session_id',
                                $id
                            ),
                    ]
                ),
        ];
    }

    public function cleanupMaster(
        string $type,
        int $id,
        int $universityId,
        int $actorId
    ): array {
        $this->assertCleanupEnabled();

        return match ($type) {
            'courses' =>
                $this->cleanupCourse(
                    $id,
                    $universityId,
                    $actorId
                ),
            'course_categories' =>
                $this->cleanupSimpleMaster(
                    'course_categories',
                    'Course Category',
                    $id,
                    $universityId,
                    [
                        ['courses', 'course_category_id'],
                        [
                            'academic_policy_credit_category_requirements',
                            'course_category_id',
                        ],
                    ],
                    $actorId
                ),
            'course_types' =>
                $this->cleanupSimpleMaster(
                    'course_types',
                    'Course Type',
                    $id,
                    $universityId,
                    [
                        ['courses', 'course_type_id'],
                    ],
                    $actorId
                ),
            'program_templates' =>
                $this->cleanupSimpleMaster(
                    'program_templates',
                    'Program Template',
                    $id,
                    $universityId,
                    [
                        ['curricula', 'program_template_id'],
                        [
                            'program_template_disciplines',
                            'program_template_id',
                        ],
                        ['academic_policies', 'program_template_id'],
                    ],
                    $actorId
                ),
            'disciplines' =>
                $this->cleanupDiscipline(
                    $id,
                    $universityId,
                    $actorId
                ),
            'academic_sessions' =>
                $this->cleanupSimpleMaster(
                    'academic_sessions',
                    'Academic Session',
                    $id,
                    $universityId,
                    [
                        ['curricula', 'academic_session_id'],
                        ['academic_policies', 'academic_session_id'],
                    ],
                    $actorId
                ),
            default => throw ValidationException::withMessages([
                'type' => 'Unsupported cleanup type.',
            ]),
        };
    }

    private function cleanupCourse(
        int $id,
        int $universityId,
        int $actorId
    ): array {
        $course = DB::table('courses')
            ->where('id', $id)
            ->where('university_id', $universityId)
            ->first();

        if (! $course) {
            abort(404);
        }

        $mappingCount = $this->countIfExists(
            'curriculum_course_mappings',
            'course_id',
            $id
        );

        $operationalRefs = $this->downstreamReferences(
            $id,
            [
                ['course_offerings', 'course_id'],
                ['student_course_registrations', 'course_id'],
                ['student_course_enrollments', 'course_id'],
                ['attendance_records', 'course_id'],
                ['internal_marks', 'course_id'],
                ['exam_marks', 'course_id'],
            ]
        );

        if (count($operationalRefs) > 0) {
            throw ValidationException::withMessages([
                'record' =>
                    'This Course has operational references and cannot be cleaned.',
            ]);
        }

        return DB::transaction(function () use (
            $course,
            $mappingCount,
            $actorId
        ) {
            if (Schema::hasTable('curriculum_course_mappings')) {
                DB::table('curriculum_course_mappings')
                    ->where('course_id', $course->id)
                    ->delete();
            }

            DB::table('courses')
                ->where('id', $course->id)
                ->delete();

            $preview = [
                'course' => (array) $course,
                'removed_curriculum_mappings' => $mappingCount,
            ];

            $this->audit(
                'TEST_COURSE_DATA_CLEANED',
                'test_data_cleanup',
                $course->id,
                $preview,
                $actorId
            );

            return $preview;
        });
    }

    private function cleanupDiscipline(
        int $id,
        int $universityId,
        int $actorId
    ): array {
        $record = DB::table('academic_disciplines')
            ->where('id', $id)
            ->where('university_id', $universityId)
            ->first();

        if (! $record) {
            abort(404);
        }

        $mappingCount = $this->disciplineMappingCount($id);
        $templateCount = $this->countIfExists(
            'program_template_disciplines',
            'discipline_id',
            $id
        );

        if ($mappingCount > 0 || $templateCount > 0) {
            throw ValidationException::withMessages([
                'record' =>
                    'Discipline/Specialization is still linked to a Program Template or Curriculum Mapping. Clean those dependent test records first.',
            ]);
        }

        DB::table('academic_disciplines')
            ->where('id', $id)
            ->delete();

        $this->audit(
            'TEST_DISCIPLINE_DATA_CLEANED',
            'test_data_cleanup',
            $id,
            ['record' => (array) $record],
            $actorId
        );

        return ['record' => (array) $record];
    }

    private function cleanupSimpleMaster(
        string $table,
        string $label,
        int $id,
        int $universityId,
        array $dependencies,
        int $actorId
    ): array {
        if (! Schema::hasTable($table)) {
            abort(404);
        }

        $record = DB::table($table)
            ->where('id', $id)
            ->where('university_id', $universityId)
            ->first();

        if (! $record) {
            abort(404);
        }

        $blocked = [];

        foreach ($dependencies as [$depTable, $depColumn]) {
            $count = $this->countIfExists(
                $depTable,
                $depColumn,
                $id
            );

            if ($count > 0) {
                $blocked[] = [
                    'table' => $depTable,
                    'column' => $depColumn,
                    'count' => $count,
                ];
            }
        }

        if (count($blocked) > 0) {
            throw ValidationException::withMessages([
                'record' =>
                    "{$label} still has dependent test records. Clean those records first.",
            ]);
        }

        DB::table($table)->where('id', $id)->delete();

        $this->audit(
            'TEST_MASTER_DATA_CLEANED',
            'test_data_cleanup',
            $id,
            [
                'table' => $table,
                'record' => (array) $record,
            ],
            $actorId
        );

        return [
            'table' => $table,
            'record' => (array) $record,
        ];
    }

    private function courseRows(int $universityId): array
    {
        if (! Schema::hasTable('courses')) {
            return [];
        }

        return DB::table('courses')
            ->where('university_id', $universityId)
            ->orderBy('name')
            ->get()
            ->map(function ($row) {
                $operationalRefs =
                    $this->downstreamReferences(
                        $row->id,
                        [
                            ['course_offerings', 'course_id'],
                            [
                                'student_course_registrations',
                                'course_id',
                            ],
                            [
                                'student_course_enrollments',
                                'course_id',
                            ],
                        ]
                    );

                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => $row->name,
                    'status' => $row->status ?? null,
                    'dependencies' => [
                        'curriculum_mappings' =>
                            $this->countIfExists(
                                'curriculum_course_mappings',
                                'course_id',
                                $row->id
                            ),
                    ],
                    'blocked' => count($operationalRefs) > 0,
                    'blocking_references' => $operationalRefs,
                ];
            })
            ->values()
            ->all();
    }

    private function simpleRows(
        string $table,
        int $universityId,
        callable $dependencyResolver
    ): array {
        if (! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)
            ->where('university_id', $universityId)
            ->orderBy('name')
            ->get()
            ->map(function ($row) use ($dependencyResolver) {
                $dependencies = $dependencyResolver($row->id);

                return [
                    'id' => $row->id,
                    'code' => $row->code ?? (string) $row->id,
                    'name' => $row->name ?? $row->code ?? (string) $row->id,
                    'status' => $row->status ?? null,
                    'kind' => $row->kind ?? null,
                    'dependencies' => $dependencies,
                    'blocked' =>
                        collect($dependencies)
                            ->filter(fn ($count) =>
                                (int) $count > 0
                            )
                            ->isNotEmpty(),
                    'blocking_references' => [],
                ];
            })
            ->values()
            ->all();
    }

    private function disciplineMappingCount(int $id): int
    {
        if (! Schema::hasTable('curriculum_course_mappings')) {
            return 0;
        }

        return DB::table('curriculum_course_mappings')
            ->where(function ($query) use ($id) {
                $query->where('discipline_id', $id)
                    ->orWhere('specialization_id', $id);
            })
            ->count();
    }


    private function academicPolicyChainIds(int $policyId): Collection
    {
        if (! Schema::hasTable('academic_policies')) {
            return collect([$policyId]);
        }

        $rootId = $policyId;

        while (true) {
            $parentId = DB::table('academic_policies')
                ->where('id', $rootId)
                ->value('parent_policy_id');

            if (! $parentId) {
                break;
            }

            $rootId = (int) $parentId;
        }

        $ids = collect([$rootId]);
        $frontier = collect([$rootId]);

        while ($frontier->isNotEmpty()) {
            $children = DB::table('academic_policies')
                ->whereIn('parent_policy_id', $frontier)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->reject(fn ($id) => $ids->contains($id))
                ->values();

            if ($children->isEmpty()) {
                break;
            }

            $ids = $ids->merge($children)->unique()->values();
            $frontier = $children;
        }

        return $ids;
    }

    private function countWhereIn(
        string $table,
        string $column,
        Collection $ids
    ): int {
        if (
            $ids->isEmpty() ||
            ! Schema::hasTable($table) ||
            ! Schema::hasColumn($table, $column)
        ) {
            return 0;
        }

        return DB::table($table)
            ->whereIn($column, $ids)
            ->count();
    }

    private function deleteWhereIn(
        string $table,
        string $column,
        Collection $ids
    ): void {
        if (
            $ids->isEmpty() ||
            ! Schema::hasTable($table) ||
            ! Schema::hasColumn($table, $column)
        ) {
            return;
        }

        DB::table($table)
            ->whereIn($column, $ids)
            ->delete();
    }

    private function downstreamReferencesForIds(
        Collection $ids,
        array $references
    ): array {
        if ($ids->isEmpty()) {
            return [];
        }

        $found = [];

        foreach ($references as [$table, $column]) {
            if (
                ! Schema::hasTable($table) ||
                ! Schema::hasColumn($table, $column)
            ) {
                continue;
            }

            $count = DB::table($table)
                ->whereIn($column, $ids)
                ->count();

            if ($count > 0) {
                $found[] = [
                    'table' => $table,
                    'column' => $column,
                    'count' => $count,
                ];
            }
        }

        return $found;
    }

    private function countIfExists(
        string $table,
        string $column,
        int $id
    ): int {
        if (
            ! Schema::hasTable($table) ||
            ! Schema::hasColumn($table, $column)
        ) {
            return 0;
        }

        return DB::table($table)
            ->where($column, $id)
            ->count();
    }

    private function downstreamReferences(
        int $id,
        array $references
    ): array {
        $found = [];

        foreach ($references as [$table, $column]) {
            if (
                ! Schema::hasTable($table) ||
                ! Schema::hasColumn($table, $column)
            ) {
                continue;
            }

            $count = DB::table($table)
                ->where($column, $id)
                ->count();

            if ($count > 0) {
                $found[] = [
                    'table' => $table,
                    'column' => $column,
                    'count' => $count,
                ];
            }
        }

        return $found;
    }

    private function assertCleanupEnabled(): void
    {
        if (! config('test-data-cleanup.enabled')) {
            throw ValidationException::withMessages([
                'cleanup' =>
                    'Test Data Cleanup is disabled in this environment.',
            ]);
        }
    }

    private function audit(
        string $event,
        string $type,
        int $id,
        array $before,
        int $actorId
    ): void {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('audit_logs')->insert([
            'event' => $event,
            'resource_type' => $type,
            'resource_id' => $id,
            'before' => json_encode($before),
            'after' => null,
            'actor_user_id' => $actorId,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
