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
        ['college_program_offerings', 'curriculum_id'],
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
            'college_program_offerings' =>
                $this->collegeProgramOfferingRows($universityId),
            'academic_calendars' =>
                $this->academicCalendarRows($universityId),
            'approval_workflows' =>
                $this->approvalWorkflowRows($universityId),
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
                        'discipline_mappings' =>
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
                        'college_program_offerings' =>
                            $this->countIfExists(
                                'college_program_offerings',
                                'program_template_id',
                                $id
                            ),
                    ]
                ),
            'disciplines' =>
                $this->disciplineRows($universityId),
            'degrees' =>
                $this->simpleRows(
                    'degrees',
                    $universityId,
                    fn ($id) => [
                        'program_templates' =>
                            $this->countIfExists(
                                'program_templates',
                                'degree_id',
                                $id
                            ),
                    ]
                ),
            'degree_levels' =>
                $this->simpleRows(
                    'degree_levels',
                    $universityId,
                    fn ($id) => [
                        'degrees' => $this->countIfExists(
                            'degrees',
                            'degree_level_id',
                            $id
                        ),
                        'academic_policies' =>
                            $this->countIfExists(
                                'academic_policies',
                                'degree_level_id',
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
                        'academic_calendars' =>
                            $this->countIfExists(
                                'academic_calendars',
                                'academic_session_id',
                                $id
                            ),
                        'college_program_offerings' =>
                            $this->countIfExists(
                                'college_program_offerings',
                                'academic_session_id',
                                $id
                            ),
                    ]
                ),
        ];
    }

    public function fullAcademicResetPreview(int $universityId): array
    {
        return [
            'confirmation_code' => 'RESET-ACADEMIC-TEST-DATA',
            'counts' => [
                'college_program_offerings' =>
                    $this->countCollegeOfferingsForUniversity($universityId),
                'academic_calendar_events' =>
                    $this->countCalendarEventsForUniversity($universityId),
                'academic_calendars' =>
                    $this->countUniversityRows('academic_calendars', $universityId),
                'approval_requests' =>
                    $this->countUniversityRows('approval_requests', $universityId),
                'approval_workflows' =>
                    $this->countUniversityRows('approval_workflows', $universityId),
                'academic_policies' =>
                    $this->countUniversityRows('academic_policies', $universityId),
                'curricula' =>
                    $this->countUniversityRows('curricula', $universityId),
                'courses' =>
                    $this->countUniversityRows('courses', $universityId),
                'program_templates' =>
                    $this->countUniversityRows('program_templates', $universityId),
                'disciplines' =>
                    $this->countUniversityRows('academic_disciplines', $universityId),
                'course_categories' =>
                    $this->countUniversityRows('course_categories', $universityId),
                'course_types' =>
                    $this->countUniversityRows('course_types', $universityId),
                'degrees' =>
                    $this->countUniversityRows('degrees', $universityId),
                'degree_levels' =>
                    $this->countUniversityRows('degree_levels', $universityId),
                'academic_sessions' =>
                    $this->countUniversityRows('academic_sessions', $universityId),
            ],
            'preserved' => [
                'University Profile',
                'Affiliated Colleges',
                'Users and login accounts',
                'Protected/system Roles',
                'Permissions and Role-Permission grants',
                'College role/scope assignments',
                'Authorized Signatories',
                'Audit Logs',
                'Laravel migrations/system tables',
            ],
        ];
    }

    public function fullAcademicReset(
        int $universityId,
        int $actorId
    ): array {
        $this->assertCleanupEnabled();

        $preview = $this->fullAcademicResetPreview($universityId);

        return DB::transaction(function () use (
            $universityId,
            $actorId,
            $preview
        ) {
            $collegeIds = Schema::hasTable('colleges')
                ? DB::table('colleges')
                    ->where('university_id', $universityId)
                    ->pluck('id')
                : collect();

            $sessionIds = $this->universityIds(
                'academic_sessions',
                $universityId
            );
            $calendarIds = $this->universityIds(
                'academic_calendars',
                $universityId
            );
            $policyIds = $this->universityIds(
                'academic_policies',
                $universityId
            );
            $curriculumIds = $this->universityIds(
                'curricula',
                $universityId
            );
            $workflowIds = $this->universityIds(
                'approval_workflows',
                $universityId
            );
            $templateIds = $this->universityIds(
                'program_templates',
                $universityId
            );
            $disciplineIds = $this->universityIds(
                'academic_disciplines',
                $universityId
            );
            $degreeIds = $this->universityIds(
                'degrees',
                $universityId
            );
            $degreeLevelIds = $this->universityIds(
                'degree_levels',
                $universityId
            );

            /*
             * Dependency-safe reset order:
             * leaf/transactional rows -> configured children -> parent masters.
             * Do not disable FK checks and do not TRUNCATE.
             */

            // 1) Approval execution history before subjects/workflows.
            $approvalRequestIds = Schema::hasTable('approval_requests')
                ? DB::table('approval_requests')
                    ->where('university_id', $universityId)
                    ->pluck('id')
                : collect();

            $this->deleteWhereIn(
                'approval_request_stages',
                'approval_request_id',
                $approvalRequestIds
            );
            $this->deleteWhereIn(
                'approval_requests',
                'id',
                $approvalRequestIds
            );

            // 2) College operational adoption leaf rows.
            if (
                Schema::hasTable('college_program_offerings') &&
                $collegeIds->isNotEmpty()
            ) {
                DB::table('college_program_offerings')
                    ->whereIn('college_id', $collegeIds)
                    ->delete();
            }

            // 3) Calendar children before Calendar headers.
            $this->deleteWhereIn(
                'academic_calendar_events',
                'academic_calendar_id',
                $calendarIds
            );
            $this->deleteWhereIn(
                'academic_calendars',
                'id',
                $calendarIds
            );

            // 4) Academic Policy children and version links.
            $ruleSetIds = Schema::hasTable(
                'academic_policy_progression_rule_sets'
            ) && $policyIds->isNotEmpty()
                ? DB::table('academic_policy_progression_rule_sets')
                    ->whereIn('academic_policy_id', $policyIds)
                    ->pluck('id')
                : collect();

            $this->deleteWhereIn(
                'academic_policy_progression_rule_terms',
                'progression_rule_set_id',
                $ruleSetIds
            );

            foreach ([
                'academic_policy_progression_rule_sets',
                'academic_policy_progression_rules',
                'academic_policy_grade_bands',
                'academic_policy_grading_rules',
                'academic_policy_assessment_exam_rules',
                'academic_policy_attendance_rules',
                'academic_policy_credit_category_requirements',
                'academic_policy_credit_completion_rules',
            ] as $table) {
                $this->deleteWhereIn(
                    $table,
                    'academic_policy_id',
                    $policyIds
                );
            }

            if (
                Schema::hasTable('academic_policies') &&
                $policyIds->isNotEmpty()
            ) {
                $updates = ['updated_at' => now()];
                if (Schema::hasColumn('academic_policies', 'parent_policy_id')) {
                    $updates['parent_policy_id'] = null;
                }
                if (Schema::hasColumn('academic_policies', 'superseded_by_id')) {
                    $updates['superseded_by_id'] = null;
                }

                DB::table('academic_policies')
                    ->whereIn('id', $policyIds)
                    ->update($updates);

                DB::table('academic_policies')
                    ->whereIn('id', $policyIds)
                    ->delete();
            }

            // 5) Curriculum structure before Curriculum headers.
            $termIds = Schema::hasTable('curriculum_terms')
                ? DB::table('curriculum_terms')
                    ->whereIn('curriculum_id', $curriculumIds)
                    ->pluck('id')
                : collect();

            $slotIds = Schema::hasTable('curriculum_slots')
                ? DB::table('curriculum_slots')
                    ->whereIn('curriculum_term_id', $termIds)
                    ->pluck('id')
                : collect();

            $this->deleteWhereIn(
                'curriculum_course_mappings',
                'curriculum_slot_id',
                $slotIds
            );
            $this->deleteWhereIn(
                'curriculum_slots',
                'id',
                $slotIds
            );
            $this->deleteWhereIn(
                'curriculum_terms',
                'id',
                $termIds
            );

            if (
                Schema::hasTable('curricula') &&
                $curriculumIds->isNotEmpty()
            ) {
                if (
                    Schema::hasColumn(
                        'curricula',
                        'parent_curriculum_id'
                    )
                ) {
                    DB::table('curricula')
                        ->whereIn('id', $curriculumIds)
                        ->update([
                            'parent_curriculum_id' => null,
                            'updated_at' => now(),
                        ]);
                }

                DB::table('curricula')
                    ->whereIn('id', $curriculumIds)
                    ->delete();
            }

            // 6) Program Template discipline/specialization mappings.
            $templateDisciplineIds =
                Schema::hasTable('program_template_disciplines') &&
                $templateIds->isNotEmpty()
                    ? DB::table('program_template_disciplines')
                        ->whereIn('program_template_id', $templateIds)
                        ->pluck('id')
                    : collect();

            $this->deleteWhereIn(
                'program_template_discipline_specializations',
                'program_template_discipline_id',
                $templateDisciplineIds
            );
            $this->deleteWhereIn(
                'program_template_disciplines',
                'id',
                $templateDisciplineIds
            );

            // 7) Approval workflow stages before workflow headers.
            $this->deleteWhereIn(
                'approval_workflow_stages',
                'approval_workflow_id',
                $workflowIds
            );
            $this->deleteWhereIn(
                'approval_workflows',
                'id',
                $workflowIds
            );

            // 8) Remaining University academic masters, child -> parent.
            $this->deleteUniversityRows('courses', $universityId);
            $this->deleteUniversityRows(
                'program_templates',
                $universityId
            );

            // Break discipline self-parent links before deleting all.
            if (
                Schema::hasTable('academic_disciplines') &&
                $disciplineIds->isNotEmpty()
            ) {
                if (
                    Schema::hasColumn(
                        'academic_disciplines',
                        'parent_id'
                    )
                ) {
                    DB::table('academic_disciplines')
                        ->whereIn('id', $disciplineIds)
                        ->update(['parent_id' => null]);
                }

                DB::table('academic_disciplines')
                    ->whereIn('id', $disciplineIds)
                    ->delete();
            }

            $this->deleteUniversityRows(
                'course_categories',
                $universityId
            );
            $this->deleteUniversityRows(
                'course_types',
                $universityId
            );
            $this->deleteWhereIn('degrees', 'id', $degreeIds);
            $this->deleteWhereIn(
                'degree_levels',
                'id',
                $degreeLevelIds
            );
            $this->deleteWhereIn(
                'academic_sessions',
                'id',
                $sessionIds
            );

            $this->audit(
                'TEST_FULL_ACADEMIC_DATA_RESET',
                'test_data_cleanup',
                $universityId,
                [
                    'deleted_counts' => $preview['counts'],
                    'preserved' => $preview['preserved'],
                    'dependency_strategy' =>
                        'child-first explicit deletion; foreign keys kept enabled',
                ],
                $actorId
            );

            return $preview;
        });
    }

    public function cleanupMaster(
        string $type,
        int $id,
        int $universityId,
        int $actorId
    ): array {
        $this->assertCleanupEnabled();

        return match ($type) {
            'college_program_offerings' =>
                $this->cleanupCollegeProgramOffering(
                    $id,
                    $universityId,
                    $actorId
                ),
            'academic_calendars' =>
                $this->cleanupAcademicCalendar(
                    $id,
                    $universityId,
                    $actorId
                ),
            'approval_workflows' =>
                $this->cleanupApprovalWorkflow(
                    $id,
                    $universityId,
                    $actorId
                ),
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
                        [
                            'college_program_offerings',
                            'program_template_id',
                        ],
                    ],
                    $actorId
                ),
            'disciplines' =>
                $this->cleanupDiscipline(
                    $id,
                    $universityId,
                    $actorId
                ),
            'degrees' =>
                $this->cleanupSimpleMaster(
                    'degrees',
                    'Degree',
                    $id,
                    $universityId,
                    [
                        ['program_templates', 'degree_id'],
                    ],
                    $actorId
                ),
            'degree_levels' =>
                $this->cleanupSimpleMaster(
                    'degree_levels',
                    'Degree Level',
                    $id,
                    $universityId,
                    [
                        ['degrees', 'degree_level_id'],
                        ['academic_policies', 'degree_level_id'],
                    ],
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
                        ['academic_calendars', 'academic_session_id'],
                        [
                            'college_program_offerings',
                            'academic_session_id',
                        ],
                    ],
                    $actorId
                ),
            default => throw ValidationException::withMessages([
                'type' => 'Unsupported cleanup type.',
            ]),
        };
    }

    private function cleanupCollegeProgramOffering(
        int $id,
        int $universityId,
        int $actorId
    ): array {
        if (! Schema::hasTable('college_program_offerings')) {
            abort(404);
        }

        $record = DB::table('college_program_offerings as cpo')
            ->join('colleges as c', 'c.id', '=', 'cpo.college_id')
            ->where('cpo.id', $id)
            ->where('c.university_id', $universityId)
            ->select('cpo.*')
            ->first();

        if (! $record) {
            abort(404);
        }

        $downstream = $this->downstreamReferences(
            $id,
            [
                ['college_program_intakes', 'college_program_offering_id'],
                ['college_program_reservations', 'college_program_offering_id'],
                ['batches', 'college_program_offering_id'],
                ['sections', 'college_program_offering_id'],
                ['student_enrollments', 'college_program_offering_id'],
                ['admission_applications', 'college_program_offering_id'],
            ]
        );

        if (count($downstream) > 0) {
            throw ValidationException::withMessages([
                'record' =>
                    'This Program Offering already has downstream College/operational records. Clean those dependent test records first.',
            ]);
        }

        DB::table('college_program_offerings')
            ->where('id', $id)
            ->delete();

        $this->audit(
            'TEST_COLLEGE_PROGRAM_OFFERING_CLEANED',
            'test_data_cleanup',
            $id,
            ['record' => (array) $record],
            $actorId
        );

        return ['record' => (array) $record];
    }

    private function cleanupAcademicCalendar(
        int $id,
        int $universityId,
        int $actorId
    ): array {
        if (! Schema::hasTable('academic_calendars')) {
            abort(404);
        }

        $record = DB::table('academic_calendars')
            ->where('id', $id)
            ->where('university_id', $universityId)
            ->first();

        if (! $record) {
            abort(404);
        }

        $futureRefs = $this->downstreamReferences(
            $id,
            [
                ['college_academic_calendars', 'university_academic_calendar_id'],
                ['college_calendar_overrides', 'academic_calendar_id'],
            ]
        );

        if (count($futureRefs) > 0) {
            throw ValidationException::withMessages([
                'record' =>
                    'This University Academic Calendar is already referenced by College Calendar data. Clean those dependent test records first.',
            ]);
        }

        return DB::transaction(function () use (
            $record,
            $actorId
        ) {
            $eventCount = $this->countIfExists(
                'academic_calendar_events',
                'academic_calendar_id',
                $record->id
            );

            if (Schema::hasTable('academic_calendar_events')) {
                DB::table('academic_calendar_events')
                    ->where('academic_calendar_id', $record->id)
                    ->delete();
            }

            DB::table('academic_calendars')
                ->where('id', $record->id)
                ->delete();

            $result = [
                'record' => (array) $record,
                'deleted_events' => $eventCount,
            ];

            $this->audit(
                'TEST_ACADEMIC_CALENDAR_CLEANED',
                'test_data_cleanup',
                $record->id,
                $result,
                $actorId
            );

            return $result;
        });
    }

    private function cleanupApprovalWorkflow(
        int $id,
        int $universityId,
        int $actorId
    ): array {
        if (! Schema::hasTable('approval_workflows')) {
            abort(404);
        }

        $record = DB::table('approval_workflows')
            ->where('id', $id)
            ->where('university_id', $universityId)
            ->first();

        if (! $record) {
            abort(404);
        }

        $requestCount = $this->countIfExists(
            'approval_requests',
            'approval_workflow_id',
            $id
        );

        if ($requestCount > 0) {
            throw ValidationException::withMessages([
                'record' =>
                    'This Approval Workflow still has approval request history. Clean/reset the related Curriculum or Academic Policy approval data first.',
            ]);
        }

        return DB::transaction(function () use (
            $record,
            $actorId
        ) {
            $stageCount = $this->countIfExists(
                'approval_workflow_stages',
                'approval_workflow_id',
                $record->id
            );

            if (Schema::hasTable('approval_workflow_stages')) {
                DB::table('approval_workflow_stages')
                    ->where('approval_workflow_id', $record->id)
                    ->delete();
            }

            DB::table('approval_workflows')
                ->where('id', $record->id)
                ->delete();

            $result = [
                'record' => (array) $record,
                'deleted_stages' => $stageCount,
            ];

            $this->audit(
                'TEST_APPROVAL_WORKFLOW_CLEANED',
                'test_data_cleanup',
                $record->id,
                $result,
                $actorId
            );

            return $result;
        });
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
        $specializationTemplateCount = $this->countIfExists(
            'program_template_discipline_specializations',
            'specialization_id',
            $id
        );
        $childCount = $this->countIfExists(
            'academic_disciplines',
            'parent_id',
            $id
        );

        if (
            $mappingCount > 0 ||
            $templateCount > 0 ||
            $specializationTemplateCount > 0 ||
            $childCount > 0
        ) {
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

    private function collegeProgramOfferingRows(
        int $universityId
    ): array {
        if (
            ! Schema::hasTable('college_program_offerings') ||
            ! Schema::hasTable('colleges')
        ) {
            return [];
        }

        return DB::table('college_program_offerings as cpo')
            ->join('colleges as c', 'c.id', '=', 'cpo.college_id')
            ->join(
                'program_templates as pt',
                'pt.id',
                '=',
                'cpo.program_template_id'
            )
            ->join(
                'academic_sessions as s',
                's.id',
                '=',
                'cpo.academic_session_id'
            )
            ->where('c.university_id', $universityId)
            ->orderBy('c.name')
            ->orderBy('pt.name')
            ->get([
                'cpo.id',
                'cpo.status',
                'c.name as college_name',
                'c.code as college_code',
                'pt.name as program_name',
                'pt.code as program_code',
                's.name as session_name',
                's.code as session_code',
            ])
            ->map(function ($row) {
                $downstream = $this->downstreamReferences(
                    $row->id,
                    [
                        [
                            'college_program_intakes',
                            'college_program_offering_id',
                        ],
                        [
                            'college_program_reservations',
                            'college_program_offering_id',
                        ],
                        ['batches', 'college_program_offering_id'],
                        ['sections', 'college_program_offering_id'],
                        [
                            'student_enrollments',
                            'college_program_offering_id',
                        ],
                    ]
                );

                return [
                    'id' => $row->id,
                    'code' => 'OFFERING-'.$row->id,
                    'name' =>
                        $row->college_name.' · '.
                        $row->program_name.' · '.
                        $row->session_name,
                    'status' => $row->status,
                    'kind' => 'COLLEGE_PROGRAM_OFFERING',
                    'dependencies' => [],
                    'blocked' => count($downstream) > 0,
                    'blocking_references' => $downstream,
                ];
            })
            ->values()
            ->all();
    }

    private function academicCalendarRows(
        int $universityId
    ): array {
        if (! Schema::hasTable('academic_calendars')) {
            return [];
        }

        return DB::table('academic_calendars')
            ->where('university_id', $universityId)
            ->orderByDesc('id')
            ->get()
            ->map(function ($row) {
                $futureRefs = $this->downstreamReferences(
                    $row->id,
                    [
                        [
                            'college_academic_calendars',
                            'university_academic_calendar_id',
                        ],
                        [
                            'college_calendar_overrides',
                            'academic_calendar_id',
                        ],
                    ]
                );

                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => $row->name,
                    'status' => $row->status,
                    'kind' => 'UNIVERSITY_ACADEMIC_CALENDAR',
                    'dependencies' => [
                        'events' => $this->countIfExists(
                            'academic_calendar_events',
                            'academic_calendar_id',
                            $row->id
                        ),
                    ],
                    'blocked' => count($futureRefs) > 0,
                    'blocking_references' => $futureRefs,
                ];
            })
            ->values()
            ->all();
    }

    private function approvalWorkflowRows(
        int $universityId
    ): array {
        if (! Schema::hasTable('approval_workflows')) {
            return [];
        }

        return DB::table('approval_workflows')
            ->where('university_id', $universityId)
            ->orderBy('name')
            ->get()
            ->map(function ($row) {
                $requestCount = $this->countIfExists(
                    'approval_requests',
                    'approval_workflow_id',
                    $row->id
                );

                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => $row->name,
                    'status' => $row->status,
                    'kind' => $row->applies_to ?? 'APPROVAL_WORKFLOW',
                    'dependencies' => [
                        'stages' => $this->countIfExists(
                            'approval_workflow_stages',
                            'approval_workflow_id',
                            $row->id
                        ),
                        'approval_requests' => $requestCount,
                    ],
                    'blocked' => $requestCount > 0,
                    'blocking_references' =>
                        $requestCount > 0
                            ? [[
                                'table' => 'approval_requests',
                                'column' => 'approval_workflow_id',
                                'count' => $requestCount,
                            ]]
                            : [],
                ];
            })
            ->values()
            ->all();
    }

    private function disciplineRows(int $universityId): array
    {
        if (! Schema::hasTable('academic_disciplines')) {
            return [];
        }

        return DB::table('academic_disciplines')
            ->where('university_id', $universityId)
            ->orderBy('name')
            ->get()
            ->map(function ($row) {
                $dependencies = [
                    'curriculum_mappings' =>
                        $this->disciplineMappingCount($row->id),
                    'program_templates' =>
                        $this->countIfExists(
                            'program_template_disciplines',
                            'discipline_id',
                            $row->id
                        ),
                    'specialization_mappings' =>
                        $this->countIfExists(
                            'program_template_discipline_specializations',
                            'specialization_id',
                            $row->id
                        ),
                    'child_specializations' =>
                        $this->countIfExists(
                            'academic_disciplines',
                            'parent_id',
                            $row->id
                        ),
                ];

                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => $row->name,
                    'status' => $row->status ?? null,
                    'kind' => $row->kind ?? null,
                    'dependencies' => $dependencies,
                    'blocked' =>
                        collect($dependencies)
                            ->contains(fn ($count) => (int) $count > 0),
                    'blocking_references' => [],
                ];
            })
            ->values()
            ->all();
    }

    private function countUniversityRows(
        string $table,
        int $universityId
    ): int {
        if (
            ! Schema::hasTable($table) ||
            ! Schema::hasColumn($table, 'university_id')
        ) {
            return 0;
        }

        return DB::table($table)
            ->where('university_id', $universityId)
            ->count();
    }

    private function universityIds(
        string $table,
        int $universityId
    ): Collection {
        if (
            ! Schema::hasTable($table) ||
            ! Schema::hasColumn($table, 'university_id')
        ) {
            return collect();
        }

        return DB::table($table)
            ->where('university_id', $universityId)
            ->pluck('id');
    }

    private function deleteUniversityRows(
        string $table,
        int $universityId
    ): void {
        if (
            ! Schema::hasTable($table) ||
            ! Schema::hasColumn($table, 'university_id')
        ) {
            return;
        }

        DB::table($table)
            ->where('university_id', $universityId)
            ->delete();
    }

    private function countCollegeOfferingsForUniversity(
        int $universityId
    ): int {
        if (
            ! Schema::hasTable('college_program_offerings') ||
            ! Schema::hasTable('colleges')
        ) {
            return 0;
        }

        return DB::table('college_program_offerings as cpo')
            ->join('colleges as c', 'c.id', '=', 'cpo.college_id')
            ->where('c.university_id', $universityId)
            ->count();
    }

    private function countCalendarEventsForUniversity(
        int $universityId
    ): int {
        if (
            ! Schema::hasTable('academic_calendar_events') ||
            ! Schema::hasTable('academic_calendars')
        ) {
            return 0;
        }

        return DB::table('academic_calendar_events as ace')
            ->join(
                'academic_calendars as ac',
                'ac.id',
                '=',
                'ace.academic_calendar_id'
            )
            ->where('ac.university_id', $universityId)
            ->count();
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
