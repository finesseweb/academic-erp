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
            'college_admission_form_templates' =>
                $this->collegeAdmissionFormTemplateRows($universityId),
            'college_application_fee_rules' =>
                $this->collegeApplicationFeeRuleRows($universityId),
            'college_admission_scores' =>
                $this->collegeAdmissionScoreRows($universityId),
            'college_admission_applications' =>
                $this->collegeAdmissionApplicationRows($universityId),
            'college_admission_selection_rules' =>
                $this->collegeAdmissionSelectionRuleRows($universityId),
            'college_program_reservation_plans' =>
                $this->collegeReservationPlanRows($universityId),
            'reservation_categories' =>
                $this->reservationCategoryRows($universityId),
            'college_program_intakes' =>
                $this->collegeProgramIntakeRows($universityId),
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
                'college_admission_form_templates' =>
                    $this->countUniversityRows('college_admission_form_templates', $universityId),
                'college_admission_form_mappings' =>
                    $this->countUniversityRows('college_admission_form_mappings', $universityId),
                'college_application_fee_rules' =>
                    $this->countUniversityRows('college_application_fee_rules', $universityId),
                'college_admission_application_field_values' =>
                    $this->countCollegeAdmissionApplicationFieldValuesForUniversity($universityId),
                'college_admission_interviews' =>
                    $this->countCollegeAdmissionInterviewsForUniversity($universityId),
                'college_admission_scores' =>
                    $this->countCollegeAdmissionScoresForUniversity($universityId),
                'college_admission_application_choices' =>
                    $this->countCollegeAdmissionApplicationChoicesForUniversity($universityId),
                'college_admission_applications' =>
                    $this->countCollegeAdmissionApplicationsForUniversity($universityId),
                'college_admission_cycles' =>
                    $this->countCollegeAdmissionCyclesForUniversity($universityId),
                'college_admission_selection_rule_tiebreakers' =>
                    $this->countCollegeAdmissionSelectionRuleTieBreakersForUniversity($universityId),
                'college_admission_selection_rules' =>
                    $this->countCollegeAdmissionSelectionRulesForUniversity($universityId),
                'college_program_reservation_allocations' =>
                    $this->countCollegeReservationAllocationsForUniversity($universityId),
                'college_program_reservation_plans' =>
                    $this->countCollegeReservationPlansForUniversity($universityId),
                'reservation_categories' =>
                    $this->countUniversityRows('reservation_categories', $universityId),
                'college_program_intake_allocations' =>
                    $this->countCollegeIntakeAllocationsForUniversity($universityId),
                'college_program_intakes' =>
                    $this->countCollegeIntakesForUniversity($universityId),
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

            // 2) Admission transactional/configuration rows before Selection Rules / Reservation / Intake.
            if ($collegeIds->isNotEmpty()) {
                $collegeOfferingIdsForAdmission = Schema::hasTable('college_program_offerings')
                    ? DB::table('college_program_offerings')->whereIn('college_id', $collegeIds)->pluck('id')
                    : collect();
                $intakeIdsForAdmission = Schema::hasTable('college_program_intakes')
                    ? DB::table('college_program_intakes')->whereIn('college_program_offering_id', $collegeOfferingIdsForAdmission)->pluck('id')
                    : collect();

                $applicationIds = Schema::hasTable('college_admission_applications')
                    ? DB::table('college_admission_applications')->whereIn('college_id', $collegeIds)->pluck('id')
                    : collect();
                $interviewIds = Schema::hasTable('college_admission_interviews')
                    ? DB::table('college_admission_interviews')->whereIn('college_admission_application_id', $applicationIds)->pluck('id')
                    : collect();
                $this->deleteWhereIn('college_admission_interview_evaluators', 'college_admission_interview_id', $interviewIds);
                $this->deleteWhereIn('college_admission_interviews', 'id', $interviewIds);
                $this->deleteWhereIn('college_admission_scores', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_application_choices', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_applications', 'id', $applicationIds);

                // Stage 1 admission form-builder configuration is test data too.
                // Applications must be removed first because they snapshot/reference
                // the resolved template and fee rule with RESTRICT foreign keys.
                if (Schema::hasTable('college_admission_form_mappings')) {
                    DB::table('college_admission_form_mappings')->where('university_id', $universityId)->delete();
                }
                if (Schema::hasTable('college_application_fee_rules')) {
                    DB::table('college_application_fee_rules')->where('university_id', $universityId)->delete();
                }
                if (Schema::hasTable('college_admission_form_templates')) {
                    // Field conditions use RESTRICT on source_field_id. Delete all
                    // condition rows belonging to this University's template fields
                    // before relying on the template -> step -> field cascades.
                    $templateIdsForCleanup = DB::table('college_admission_form_templates')->where('university_id', $universityId)->pluck('id');
                    $stepIdsForCleanup = Schema::hasTable('college_admission_form_steps')
                        ? DB::table('college_admission_form_steps')->whereIn('college_admission_form_template_id', $templateIdsForCleanup)->pluck('id')
                        : collect();
                    $fieldIdsForCleanup = Schema::hasTable('college_admission_form_fields')
                        ? DB::table('college_admission_form_fields')->whereIn('college_admission_form_step_id', $stepIdsForCleanup)->pluck('id')
                        : collect();
                    if ($fieldIdsForCleanup->isNotEmpty() && Schema::hasTable('college_admission_form_field_conditions')) {
                        DB::table('college_admission_form_field_conditions')
                            ->whereIn('college_admission_form_field_id', $fieldIdsForCleanup)
                            ->orWhereIn('source_field_id', $fieldIdsForCleanup)
                            ->delete();
                    }

                    // Break parent inheritance links before deleting the template tree.
                    DB::table('college_admission_form_templates')->where('university_id', $universityId)->update(['parent_template_id' => null]);
                    DB::table('college_admission_form_templates')->where('university_id', $universityId)->delete();
                }

                $selectionRuleIds = Schema::hasTable('college_admission_selection_rules')
                    ? DB::table('college_admission_selection_rules')->whereIn('college_program_intake_id', $intakeIdsForAdmission)->pluck('id')
                    : collect();
                $this->deleteWhereIn('college_admission_selection_rule_tiebreakers', 'college_admission_selection_rule_id', $selectionRuleIds);
                $this->deleteWhereIn('college_admission_selection_rules', 'id', $selectionRuleIds);

                if (Schema::hasTable('college_admission_cycles')) {
                    DB::table('college_admission_cycles')->whereIn('college_id', $collegeIds)->delete();
                }
            }

            // 3) Reservation / Quota before Intake.
            if (
                Schema::hasTable('college_program_reservation_plans') &&
                Schema::hasTable('college_program_reservation_allocations') &&
                $collegeIds->isNotEmpty()
            ) {
                $offeringIdsForReservation =
                    DB::table('college_program_offerings')
                        ->whereIn('college_id', $collegeIds)
                        ->pluck('id');

                $intakeIdsForReservation =
                    DB::table('college_program_intakes')
                        ->whereIn('college_program_offering_id', $offeringIdsForReservation)
                        ->pluck('id');

                $planIds = DB::table('college_program_reservation_plans')
                    ->whereIn('college_program_intake_id', $intakeIdsForReservation)
                    ->pluck('id');

                $this->deleteWhereIn(
                    'college_program_reservation_allocations',
                    'college_program_reservation_plan_id',
                    $planIds
                );
                $this->deleteWhereIn(
                    'college_program_reservation_plans',
                    'id',
                    $planIds
                );
            }

            // 2) College operational setup: allocations -> intake -> offering.
            if (
                Schema::hasTable('college_program_intakes') &&
                Schema::hasTable('college_program_offerings') &&
                $collegeIds->isNotEmpty()
            ) {
                $collegeOfferingIds = DB::table('college_program_offerings')
                    ->whereIn('college_id', $collegeIds)
                    ->pluck('id');

                $intakeIds = DB::table('college_program_intakes')
                    ->whereIn(
                        'college_program_offering_id',
                        $collegeOfferingIds
                    )
                    ->pluck('id');

                /*
                 * Hierarchical Intake cleanup:
                 * specialization child allocations -> discipline allocations
                 * -> intake header.
                 */
                if (
                    Schema::hasTable(
                        'college_program_intake_allocations'
                    ) &&
                    $intakeIds->isNotEmpty()
                ) {
                    if (
                        Schema::hasColumn(
                            'college_program_intake_allocations',
                            'parent_allocation_id'
                        )
                    ) {
                        DB::table(
                            'college_program_intake_allocations'
                        )
                            ->whereIn(
                                'college_program_intake_id',
                                $intakeIds
                            )
                            ->whereNotNull(
                                'parent_allocation_id'
                            )
                            ->delete();

                        DB::table(
                            'college_program_intake_allocations'
                        )
                            ->whereIn(
                                'college_program_intake_id',
                                $intakeIds
                            )
                            ->whereNull(
                                'parent_allocation_id'
                            )
                            ->delete();
                    } else {
                        DB::table(
                            'college_program_intake_allocations'
                        )
                            ->whereIn(
                                'college_program_intake_id',
                                $intakeIds
                            )
                            ->delete();
                    }
                }

                $this->deleteWhereIn(
                    'college_program_intakes',
                    'id',
                    $intakeIds
                );
            }

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

            // 8) Reservation category master after College Reservation data.
            $this->deleteUniversityRows('reservation_categories', $universityId);

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
            'college_admission_form_templates' =>
                $this->cleanupCollegeAdmissionFormTemplate($id, $universityId, $actorId),
            'college_application_fee_rules' =>
                $this->cleanupCollegeApplicationFeeRule($id, $universityId, $actorId),
            'college_admission_scores' =>
                $this->cleanupCollegeAdmissionScore($id, $universityId, $actorId),
            'college_admission_applications' =>
                $this->cleanupCollegeAdmissionApplication(
                    $id,
                    $universityId,
                    $actorId
                ),
            'college_admission_selection_rules' =>
                $this->cleanupCollegeAdmissionSelectionRule(
                    $id,
                    $universityId,
                    $actorId
                ),
            'college_program_reservation_plans' =>
                $this->cleanupCollegeReservationPlan(
                    $id,
                    $universityId,
                    $actorId
                ),
            'reservation_categories' =>
                $this->cleanupSimpleMaster(
                    'reservation_categories',
                    'Reservation / Quota Category',
                    $id,
                    $universityId,
                    [
                        ['college_program_reservation_allocations', 'reservation_category_id'],
                    ],
                    $actorId
                ),
            'college_program_intakes' =>
                $this->cleanupCollegeProgramIntake(
                    $id,
                    $universityId,
                    $actorId
                ),
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

    private function collegeAdmissionFormTemplateRows(int $universityId): array
    {
        if (! Schema::hasTable('college_admission_form_templates')) return [];

        return DB::table('college_admission_form_templates as t')
            ->leftJoin('colleges as c', 'c.id', '=', 't.college_id')
            ->where('t.university_id', $universityId)
            ->orderByDesc('t.id')
            ->get(['t.id', 't.code', 't.name', 't.status', 't.owner_scope_type', 'c.name as college_name'])
            ->map(function ($row) {
                $dependencies = array_filter([
                    'applications' => $this->countIfExists('college_admission_applications', 'college_admission_form_template_id', $row->id),
                    'child_templates' => $this->countIfExists('college_admission_form_templates', 'parent_template_id', $row->id),
                ]);
                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => $row->name.($row->college_name ? ' · '.$row->college_name : ' · University'),
                    'status' => $row->status,
                    'kind' => 'ADMISSION_FORM_TEMPLATE',
                    'dependencies' => [],
                    'blocked' => count($dependencies) > 0,
                    'blocking_references' => $dependencies,
                ];
            })->all();
    }

    private function cleanupCollegeAdmissionFormTemplate(int $id, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('college_admission_form_templates')) abort(404);
        $record = DB::table('college_admission_form_templates')->where('id', $id)->where('university_id', $universityId)->first();
        if (! $record) abort(404);

        $downstream = array_filter([
            'applications' => $this->countIfExists('college_admission_applications', 'college_admission_form_template_id', $id),
            'child_templates' => $this->countIfExists('college_admission_form_templates', 'parent_template_id', $id),
        ]);
        if (count($downstream) > 0) {
            throw ValidationException::withMessages(['record' => 'This Admission Form Template is already referenced by an Application or child template. Clean those dependent test records first.']);
        }

        $before = (array) $record;

        // Field conditions use RESTRICT on source_field_id so a template tree cannot
        // rely on step/field cascades alone. Remove condition rows first for every
        // field owned by this template before deleting the template.
        $stepIds = Schema::hasTable('college_admission_form_steps')
            ? DB::table('college_admission_form_steps')->where('college_admission_form_template_id', $id)->pluck('id')
            : collect();
        $fieldIds = Schema::hasTable('college_admission_form_fields')
            ? DB::table('college_admission_form_fields')->whereIn('college_admission_form_step_id', $stepIds)->pluck('id')
            : collect();
        if ($fieldIds->isNotEmpty() && Schema::hasTable('college_admission_form_field_conditions')) {
            DB::table('college_admission_form_field_conditions')
                ->whereIn('college_admission_form_field_id', $fieldIds)
                ->orWhereIn('source_field_id', $fieldIds)
                ->delete();
        }

        DB::table('college_admission_form_templates')->where('id', $id)->delete();
        $result = ['deleted' => 1, 'record' => $before];
        $this->audit('TEST_ADMISSION_FORM_TEMPLATE_CLEANED', 'test_data_cleanup', $id, $result, $actorId);
        return $result;
    }

    private function collegeApplicationFeeRuleRows(int $universityId): array
    {
        if (! Schema::hasTable('college_application_fee_rules')) return [];

        return DB::table('college_application_fee_rules as r')
            ->leftJoin('colleges as c', 'c.id', '=', 'r.college_id')
            ->where('r.university_id', $universityId)
            ->orderByDesc('r.id')
            ->get(['r.id', 'r.name', 'r.status', 'r.amount', 'r.currency', 'r.fee_required', 'c.name as college_name'])
            ->map(function ($row) {
                $applications = $this->countIfExists('college_admission_applications', 'application_fee_rule_id', $row->id);
                return [
                    'id' => $row->id,
                    'code' => $row->fee_required ? $row->currency.' '.$row->amount : 'FREE',
                    'name' => $row->name.($row->college_name ? ' · '.$row->college_name : ' · University'),
                    'status' => $row->status,
                    'kind' => 'APPLICATION_FEE_RULE',
                    'dependencies' => [],
                    'blocked' => $applications > 0,
                    'blocking_references' => $applications > 0 ? ['applications' => $applications] : [],
                ];
            })->all();
    }

    private function cleanupCollegeApplicationFeeRule(int $id, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('college_application_fee_rules')) abort(404);
        $record = DB::table('college_application_fee_rules')->where('id', $id)->where('university_id', $universityId)->first();
        if (! $record) abort(404);

        $applications = $this->countIfExists('college_admission_applications', 'application_fee_rule_id', $id);
        if ($applications > 0) {
            throw ValidationException::withMessages(['record' => 'This Application Fee Rule is already snapshotted/referenced by an Application. Clean the dependent test Application first.']);
        }

        $before = (array) $record;
        DB::table('college_application_fee_rules')->where('id', $id)->delete();
        $result = ['deleted' => 1, 'record' => $before];
        $this->audit('TEST_APPLICATION_FEE_RULE_CLEANED', 'test_data_cleanup', $id, $result, $actorId);
        return $result;
    }

    private function countCollegeAdmissionApplicationFieldValuesForUniversity(int $universityId): int
    {
        if (! Schema::hasTable('college_admission_application_field_values') || ! Schema::hasTable('college_admission_applications')) return 0;
        return DB::table('college_admission_application_field_values as v')
            ->join('college_admission_applications as a', 'a.id', '=', 'v.college_admission_application_id')
            ->join('colleges as c', 'c.id', '=', 'a.college_id')
            ->where('c.university_id', $universityId)
            ->count();
    }

    private function cleanupCollegeAdmissionScore(int $id, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('college_admission_scores')) abort(404);
        $record = DB::table('college_admission_scores as s')
            ->join('college_admission_applications as a', 'a.id', '=', 's.college_admission_application_id')
            ->join('colleges as c', 'c.id', '=', 'a.college_id')
            ->where('s.id', $id)->where('c.university_id', $universityId)
            ->select('s.*', 'a.application_no', 'a.candidate_name', 'c.name as college_name')->first();
        if (! $record) abort(404);
        $downstream = $this->downstreamReferences($id, [
            ['college_admission_merit_entries', 'college_admission_score_id'],
            ['college_admission_seat_allocations', 'college_admission_score_id'],
            ['admissions', 'college_admission_score_id'],
        ]);
        if (count($downstream) > 0) throw ValidationException::withMessages(['record'=>'This normalized Score is already consumed by downstream Merit / Seat / Admission records. Clean those dependent test records first.']);
        DB::table('college_admission_scores')->where('id', $record->id)->delete();
        $result=['record'=>(array)$record];
        $this->audit('TEST_COLLEGE_ADMISSION_SCORE_CLEANED','test_data_cleanup',$record->id,$result,$actorId);
        return $result;
    }

    private function collegeAdmissionInterviewRows(int $universityId): array
    {
        if (! Schema::hasTable('college_admission_interviews') || ! Schema::hasTable('college_admission_applications')) return [];
        return DB::table('college_admission_interviews as i')->join('college_admission_applications as a','a.id','=','i.college_admission_application_id')->join('colleges as c','c.id','=','a.college_id')->where('c.university_id',$universityId)->select(['i.id','i.panel_name as name','i.status','c.name as college_name'])->orderByDesc('i.id')->get()->map(fn($r)=>(array)$r)->all();
    }

    private function collegeAdmissionScoreRows(int $universityId): array
    {
        if (! Schema::hasTable('college_admission_scores')) return [];
        return DB::table('college_admission_scores as s')
            ->join('college_admission_applications as a','a.id','=','s.college_admission_application_id')
            ->join('college_admission_application_choices as ch','ch.id','=','s.college_admission_application_choice_id')
            ->join('colleges as c','c.id','=','a.college_id')
            ->where('c.university_id',$universityId)->orderByDesc('s.id')
            ->get(['s.id','s.qualification_status','s.final_weighted_score','a.application_no','a.candidate_name','ch.preference_no','c.name as college_name'])
            ->map(function($row){
                $downstream=$this->downstreamReferences($row->id,[
                    ['college_admission_merit_entries','college_admission_score_id'],
                    ['college_admission_seat_allocations','college_admission_score_id'],
                    ['admissions','college_admission_score_id'],
                ]);
                return ['id'=>$row->id,'code'=>$row->application_no.'-P'.$row->preference_no,'name'=>$row->candidate_name.' · '.$row->college_name,'status'=>$row->qualification_status,'kind'=>'NORMALIZED_SCORE','dependencies'=>[],'blocked'=>count($downstream)>0,'blocking_references'=>$downstream];
            })->values()->all();
    }

    private function cleanupCollegeAdmissionApplication(
        int $id,
        int $universityId,
        int $actorId
    ): array {
        if (! Schema::hasTable('college_admission_applications')) {
            abort(404);
        }

        $record = DB::table('college_admission_applications as a')
            ->join('colleges as c', 'c.id', '=', 'a.college_id')
            ->where('a.id', $id)
            ->where('c.university_id', $universityId)
            ->select('a.*', 'c.name as college_name')
            ->first();

        if (! $record) {
            abort(404);
        }

        $downstream = $this->downstreamReferences(
            $id,
            [
                ['college_admission_scores', 'college_admission_application_id'],
                ['college_admission_interviews', 'college_admission_application_id'],
                ['college_admission_merit_entries', 'college_admission_application_id'],
                ['college_admission_seat_allocations', 'college_admission_application_id'],
                ['admissions', 'college_admission_application_id'],
                ['students', 'college_admission_application_id'],
            ]
        );

        if (count($downstream) > 0) {
            throw ValidationException::withMessages([
                'record' => 'This Admission Application already has downstream Score / Interview / Merit / Seat / Student references. Clean those dependent test records first.',
            ]);
        }

        return DB::transaction(function () use ($record, $actorId) {
            $choiceCount = $this->countIfExists(
                'college_admission_application_choices',
                'college_admission_application_id',
                $record->id
            );
            $this->deleteWhereIn(
                'college_admission_application_choices',
                'college_admission_application_id',
                collect([$record->id])
            );
            DB::table('college_admission_applications')->where('id', $record->id)->delete();

            $result = [
                'record' => (array) $record,
                'deleted_choices' => $choiceCount,
            ];
            $this->audit(
                'TEST_COLLEGE_ADMISSION_APPLICATION_CLEANED',
                'test_data_cleanup',
                $record->id,
                $result,
                $actorId
            );
            return $result;
        });
    }

    private function collegeAdmissionApplicationRows(int $universityId): array
    {
        if (! Schema::hasTable('college_admission_applications')) {
            return [];
        }

        return DB::table('college_admission_applications as a')
            ->join('colleges as c', 'c.id', '=', 'a.college_id')
            ->leftJoin('college_admission_cycles as ac', 'ac.id', '=', 'a.college_admission_cycle_id')
            ->where('c.university_id', $universityId)
            ->orderByDesc('a.id')
            ->get([
                'a.id', 'a.application_no', 'a.candidate_name', 'a.status',
                'c.name as college_name', 'ac.name as cycle_name',
            ])
            ->map(function ($row) {
                $downstream = $this->downstreamReferences(
                    $row->id,
                    [
                        ['college_admission_scores', 'college_admission_application_id'],
                        ['college_admission_interviews', 'college_admission_application_id'],
                        ['college_admission_merit_entries', 'college_admission_application_id'],
                        ['college_admission_seat_allocations', 'college_admission_application_id'],
                        ['admissions', 'college_admission_application_id'],
                        ['students', 'college_admission_application_id'],
                    ]
                );

                return [
                    'id' => $row->id,
                    'code' => $row->application_no,
                    'name' => $row->candidate_name.' · '.$row->college_name.' · '.($row->cycle_name ?? 'Admission Cycle'),
                    'status' => $row->status,
                    'kind' => 'APPLICATION',
                    'dependencies' => [
                        'program_choices' => $this->countIfExists(
                            'college_admission_application_choices',
                            'college_admission_application_id',
                            $row->id
                        ),
                    ],
                    'blocked' => count($downstream) > 0,
                    'blocking_references' => $downstream,
                ];
            })
            ->values()
            ->all();
    }

    private function cleanupCollegeAdmissionSelectionRule(
        int $id,
        int $universityId,
        int $actorId
    ): array {
        if (! Schema::hasTable('college_admission_selection_rules')) {
            abort(404);
        }

        $record = DB::table('college_admission_selection_rules as sr')
            ->join('college_program_intakes as i', 'i.id', '=', 'sr.college_program_intake_id')
            ->join('college_program_offerings as o', 'o.id', '=', 'i.college_program_offering_id')
            ->join('colleges as c', 'c.id', '=', 'o.college_id')
            ->where('sr.id', $id)
            ->where('c.university_id', $universityId)
            ->select('sr.*', 'c.name as college_name')
            ->first();

        if (! $record) {
            abort(404);
        }

        $downstream = $this->downstreamReferences(
            $id,
            [
                ['college_admission_application_choices', 'college_admission_selection_rule_id'],
                ['college_admission_scores', 'college_admission_selection_rule_id'],
                ['college_admission_interviews', 'college_admission_selection_rule_id'],
                ['college_admission_merit_entries', 'college_admission_selection_rule_id'],
                ['college_admission_seat_allocations', 'college_admission_selection_rule_id'],
                ['admissions', 'college_admission_selection_rule_id'],
            ]
        );

        if (count($downstream) > 0) {
            throw ValidationException::withMessages([
                'record' => 'This Selection Rule is already referenced by Admission test data. Clean the dependent Application / Score / Interview / Merit / Seat records first.',
            ]);
        }

        return DB::transaction(function () use ($record, $actorId) {
            $tieBreakerCount = $this->countIfExists(
                'college_admission_selection_rule_tiebreakers',
                'college_admission_selection_rule_id',
                $record->id
            );

            $this->deleteWhereIn(
                'college_admission_selection_rule_tiebreakers',
                'college_admission_selection_rule_id',
                collect([$record->id])
            );
            DB::table('college_admission_selection_rules')->where('id', $record->id)->delete();

            $result = [
                'record' => (array) $record,
                'deleted_tie_breakers' => $tieBreakerCount,
            ];

            $this->audit(
                'TEST_COLLEGE_ADMISSION_SELECTION_RULE_CLEANED',
                'test_data_cleanup',
                $record->id,
                $result,
                $actorId
            );

            return $result;
        });
    }

    private function collegeAdmissionSelectionRuleRows(int $universityId): array
    {
        if (! Schema::hasTable('college_admission_selection_rules')) {
            return [];
        }

        return DB::table('college_admission_selection_rules as sr')
            ->join('college_program_intakes as i', 'i.id', '=', 'sr.college_program_intake_id')
            ->join('college_program_offerings as o', 'o.id', '=', 'i.college_program_offering_id')
            ->join('colleges as c', 'c.id', '=', 'o.college_id')
            ->leftJoin('program_templates as pt', 'pt.id', '=', 'o.program_template_id')
            ->leftJoin('academic_sessions as ses', 'ses.id', '=', 'o.academic_session_id')
            ->where('c.university_id', $universityId)
            ->orderByDesc('sr.id')
            ->get([
                'sr.id', 'sr.code', 'sr.name', 'sr.version_no', 'sr.status', 'sr.selection_mode',
                'sr.bucket_type', 'sr.bucket_key', 'c.name as college_name',
                'pt.name as program_name', 'ses.name as session_name',
            ])
            ->map(function ($row) {
                $downstream = $this->downstreamReferences(
                    $row->id,
                    [
                        ['college_admission_application_choices', 'college_admission_selection_rule_id'],
                        ['college_admission_scores', 'college_admission_selection_rule_id'],
                        ['college_admission_interviews', 'college_admission_selection_rule_id'],
                        ['college_admission_merit_entries', 'college_admission_selection_rule_id'],
                        ['college_admission_seat_allocations', 'college_admission_selection_rule_id'],
                        ['admissions', 'college_admission_selection_rule_id'],
                    ]
                );

                return [
                    'id' => $row->id,
                    'code' => $row->code.' V'.$row->version_no,
                    'name' => $row->name.' · '.($row->program_name ?? 'Program').' · '.($row->session_name ?? 'Session').' · '.$row->bucket_key,
                    'status' => $row->status,
                    'kind' => $row->selection_mode,
                    'dependencies' => [
                        'tie_breakers' => $this->countIfExists(
                            'college_admission_selection_rule_tiebreakers',
                            'college_admission_selection_rule_id',
                            $row->id
                        ),
                    ],
                    'blocked' => count($downstream) > 0,
                    'blocking_references' => $downstream,
                ];
            })
            ->values()
            ->all();
    }

    private function cleanupCollegeReservationPlan(
        int $id,
        int $universityId,
        int $actorId
    ): array {
        if (! Schema::hasTable('college_program_reservation_plans')) {
            abort(404);
        }

        $record = DB::table('college_program_reservation_plans as p')
            ->join('college_program_intakes as i', 'i.id', '=', 'p.college_program_intake_id')
            ->join('college_program_offerings as o', 'o.id', '=', 'i.college_program_offering_id')
            ->join('colleges as c', 'c.id', '=', 'o.college_id')
            ->where('p.id', $id)
            ->where('c.university_id', $universityId)
            ->select('p.*')
            ->first();

        if (! $record) {
            abort(404);
        }

        $downstream = $this->downstreamReferences(
            $id,
            [
                ['college_admission_selection_rules', 'college_program_reservation_plan_id'],
                ['college_admission_application_choices', 'college_program_reservation_plan_id'],
                ['admission_applications', 'college_program_reservation_plan_id'],
                ['admissions', 'college_program_reservation_plan_id'],
                ['student_enrollments', 'college_program_reservation_plan_id'],
            ]
        );

        if (count($downstream) > 0) {
            throw ValidationException::withMessages([
                'record' =>
                    'This Reservation plan already has Admission/Student references. Clean those dependent test records first.',
            ]);
        }

        return DB::transaction(function () use ($record, $actorId) {
            $allocationCount = $this->countIfExists(
                'college_program_reservation_allocations',
                'college_program_reservation_plan_id',
                $record->id
            );

            if (Schema::hasTable('college_program_reservation_allocations')) {
                DB::table('college_program_reservation_allocations')
                    ->where('college_program_reservation_plan_id', $record->id)
                    ->delete();
            }

            DB::table('college_program_reservation_plans')
                ->where('id', $record->id)
                ->delete();

            $result = [
                'record' => (array) $record,
                'deleted_allocations' => $allocationCount,
            ];

            $this->audit(
                'TEST_COLLEGE_RESERVATION_PLAN_CLEANED',
                'test_data_cleanup',
                $record->id,
                $result,
                $actorId
            );

            return $result;
        });
    }

    private function cleanupCollegeProgramIntake(
        int $id,
        int $universityId,
        int $actorId
    ): array {
        if (
            ! Schema::hasTable('college_program_intakes') ||
            ! Schema::hasTable('college_program_offerings') ||
            ! Schema::hasTable('colleges')
        ) {
            abort(404);
        }

        $record = DB::table('college_program_intakes as cpi')
            ->join(
                'college_program_offerings as cpo',
                'cpo.id',
                '=',
                'cpi.college_program_offering_id'
            )
            ->join(
                'colleges as c',
                'c.id',
                '=',
                'cpo.college_id'
            )
            ->where('cpi.id', $id)
            ->where('c.university_id', $universityId)
            ->select([
                'cpi.*',
                'cpo.college_id',
                'cpo.program_template_id',
                'cpo.academic_session_id',
            ])
            ->first();

        if (! $record) {
            abort(404);
        }

        $downstream = $this->downstreamReferences(
            $id,
            [
                ['college_program_reservation_plans', 'college_program_intake_id'],
                ['college_admission_selection_rules', 'college_program_intake_id'],
                ['college_admission_application_choices', 'college_program_intake_id'],
                ['admission_applications', 'college_program_intake_id'],
                ['admissions', 'college_program_intake_id'],
                ['student_enrollments', 'college_program_intake_id'],
                ['students', 'college_program_intake_id'],
            ]
        );

        if (count($downstream) > 0) {
            throw ValidationException::withMessages([
                'record' =>
                    'This Intake / Seat Capacity already has downstream Reservation, Admission, or Student references. Clean those dependent test records first.',
            ]);
        }

        return DB::transaction(function () use (
            $record,
            $actorId
        ) {
            $allocationCount = $this->countIfExists(
                'college_program_intake_allocations',
                'college_program_intake_id',
                $record->id
            );

            if (
                Schema::hasTable(
                    'college_program_intake_allocations'
                )
            ) {
                if (
                    Schema::hasColumn(
                        'college_program_intake_allocations',
                        'parent_allocation_id'
                    )
                ) {
                    DB::table(
                        'college_program_intake_allocations'
                    )
                        ->where(
                            'college_program_intake_id',
                            $record->id
                        )
                        ->whereNotNull('parent_allocation_id')
                        ->delete();

                    DB::table(
                        'college_program_intake_allocations'
                    )
                        ->where(
                            'college_program_intake_id',
                            $record->id
                        )
                        ->whereNull('parent_allocation_id')
                        ->delete();
                } else {
                    DB::table(
                        'college_program_intake_allocations'
                    )
                        ->where(
                            'college_program_intake_id',
                            $record->id
                        )
                        ->delete();
                }
            }

            DB::table('college_program_intakes')
                ->where('id', $record->id)
                ->delete();

            $result = [
                'record' => (array) $record,
                'deleted_allocations' => $allocationCount,
            ];

            $this->audit(
                'TEST_COLLEGE_PROGRAM_INTAKE_CLEANED',
                'test_data_cleanup',
                $record->id,
                $result,
                $actorId
            );

            return $result;
        });
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

    private function collegeReservationPlanRows(
        int $universityId
    ): array {
        if (! Schema::hasTable('college_program_reservation_plans')) {
            return [];
        }

        return DB::table('college_program_reservation_plans as p')
            ->join('college_program_intakes as i', 'i.id', '=', 'p.college_program_intake_id')
            ->join('college_program_offerings as o', 'o.id', '=', 'i.college_program_offering_id')
            ->join('colleges as c', 'c.id', '=', 'o.college_id')
            ->leftJoin('program_templates as pt', 'pt.id', '=', 'o.program_template_id')
            ->where('c.university_id', $universityId)
            ->orderBy('c.name')
            ->orderBy('pt.name')
            ->get([
                'p.id','p.status','p.bucket_type','p.bucket_key','p.basis_capacity',
                'c.name as college_name','pt.name as program_name',
            ])
            ->map(function ($row) {
                $downstream = $this->downstreamReferences(
                    $row->id,
                    [
                        ['college_admission_selection_rules', 'college_program_reservation_plan_id'],
                        ['college_admission_application_choices', 'college_program_reservation_plan_id'],
                        ['admission_applications', 'college_program_reservation_plan_id'],
                        ['admissions', 'college_program_reservation_plan_id'],
                        ['student_enrollments', 'college_program_reservation_plan_id'],
                    ]
                );

                return [
                    'id' => $row->id,
                    'code' => 'RESERVATION-'.$row->id,
                    'name' => $row->college_name.' · '.($row->program_name ?? 'Program').
                        ' · '.$row->bucket_key.' · '.$row->basis_capacity.' seats',
                    'status' => $row->status,
                    'kind' => $row->bucket_type,
                    'dependencies' => [
                        'quota_allocations' => $this->countIfExists(
                            'college_program_reservation_allocations',
                            'college_program_reservation_plan_id',
                            $row->id
                        ),
                    ],
                    'blocked' => count($downstream) > 0,
                    'blocking_references' => $downstream,
                ];
            })
            ->values()
            ->all();
    }

    private function reservationCategoryRows(
        int $universityId
    ): array {
        if (! Schema::hasTable('reservation_categories')) {
            return [];
        }

        return DB::table('reservation_categories')
            ->where('university_id', $universityId)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->map(function ($row) {
                $used = $this->countIfExists(
                    'college_program_reservation_allocations',
                    'reservation_category_id',
                    $row->id
                );

                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => $row->name,
                    'status' => $row->status,
                    'kind' => $row->nature,
                    'dependencies' => ['reservation_allocations' => $used],
                    'blocked' => $used > 0,
                    'blocking_references' => $used > 0 ? [[
                        'table' => 'college_program_reservation_allocations',
                        'column' => 'reservation_category_id',
                        'count' => $used,
                    ]] : [],
                ];
            })
            ->values()
            ->all();
    }

    private function collegeProgramIntakeRows(
        int $universityId
    ): array {
        if (
            ! Schema::hasTable('college_program_intakes') ||
            ! Schema::hasTable('college_program_offerings') ||
            ! Schema::hasTable('colleges')
        ) {
            return [];
        }

        return DB::table('college_program_intakes as cpi')
            ->join(
                'college_program_offerings as cpo',
                'cpo.id',
                '=',
                'cpi.college_program_offering_id'
            )
            ->join(
                'colleges as c',
                'c.id',
                '=',
                'cpo.college_id'
            )
            ->leftJoin(
                'program_templates as pt',
                'pt.id',
                '=',
                'cpo.program_template_id'
            )
            ->leftJoin(
                'academic_sessions as s',
                's.id',
                '=',
                'cpo.academic_session_id'
            )
            ->where('c.university_id', $universityId)
            ->orderBy('c.name')
            ->orderBy('pt.name')
            ->get([
                'cpi.id',
                'cpi.status',
                'cpi.approved_capacity',
                'cpi.allocation_mode',
                'c.name as college_name',
                'pt.name as program_name',
                's.name as session_name',
            ])
            ->map(function ($row) {
                $downstream = $this->downstreamReferences(
                    $row->id,
                    [
                        [
                            'college_program_reservations',
                            'college_program_intake_id',
                        ],
                        [
                            'admission_applications',
                            'college_program_intake_id',
                        ],
                        ['admissions', 'college_program_intake_id'],
                        [
                            'student_enrollments',
                            'college_program_intake_id',
                        ],
                        ['students', 'college_program_intake_id'],
                    ]
                );

                return [
                    'id' => $row->id,
                    'code' => 'INTAKE-'.$row->id,
                    'name' =>
                        $row->college_name.' · '.
                        ($row->program_name ?? 'Program').' · '.
                        ($row->session_name ?? 'Session').
                        ' · '.$row->approved_capacity.' seats',
                    'status' => $row->status,
                    'kind' => $row->allocation_mode,
                    'dependencies' => [
                        'allocations' =>
                            $this->countIfExists(
                                'college_program_intake_allocations',
                                'college_program_intake_id',
                                $row->id
                            ),
                    ],
                    'blocked' => count($downstream) > 0,
                    'blocking_references' => $downstream,
                ];
            })
            ->values()
            ->all();
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

    private function countCollegeReservationPlansForUniversity(
        int $universityId
    ): int {
        if (! Schema::hasTable('college_program_reservation_plans')) {
            return 0;
        }

        return DB::table('college_program_reservation_plans as p')
            ->join('college_program_intakes as i', 'i.id', '=', 'p.college_program_intake_id')
            ->join('college_program_offerings as o', 'o.id', '=', 'i.college_program_offering_id')
            ->join('colleges as c', 'c.id', '=', 'o.college_id')
            ->where('c.university_id', $universityId)
            ->count();
    }

    private function countCollegeReservationAllocationsForUniversity(
        int $universityId
    ): int {
        if (! Schema::hasTable('college_program_reservation_allocations')) {
            return 0;
        }

        return DB::table('college_program_reservation_allocations as a')
            ->join('college_program_reservation_plans as p', 'p.id', '=', 'a.college_program_reservation_plan_id')
            ->join('college_program_intakes as i', 'i.id', '=', 'p.college_program_intake_id')
            ->join('college_program_offerings as o', 'o.id', '=', 'i.college_program_offering_id')
            ->join('colleges as c', 'c.id', '=', 'o.college_id')
            ->where('c.university_id', $universityId)
            ->count();
    }

    private function countCollegeIntakesForUniversity(
        int $universityId
    ): int {
        if (
            ! Schema::hasTable('college_program_intakes') ||
            ! Schema::hasTable('college_program_offerings') ||
            ! Schema::hasTable('colleges')
        ) {
            return 0;
        }

        return DB::table('college_program_intakes as cpi')
            ->join(
                'college_program_offerings as cpo',
                'cpo.id',
                '=',
                'cpi.college_program_offering_id'
            )
            ->join(
                'colleges as c',
                'c.id',
                '=',
                'cpo.college_id'
            )
            ->where(
                'c.university_id',
                $universityId
            )
            ->count();
    }

    private function countCollegeIntakeAllocationsForUniversity(
        int $universityId
    ): int {
        if (
            ! Schema::hasTable(
                'college_program_intake_allocations'
            ) ||
            ! Schema::hasTable('college_program_intakes') ||
            ! Schema::hasTable('college_program_offerings') ||
            ! Schema::hasTable('colleges')
        ) {
            return 0;
        }

        return DB::table(
            'college_program_intake_allocations as cpia'
        )
            ->join(
                'college_program_intakes as cpi',
                'cpi.id',
                '=',
                'cpia.college_program_intake_id'
            )
            ->join(
                'college_program_offerings as cpo',
                'cpo.id',
                '=',
                'cpi.college_program_offering_id'
            )
            ->join(
                'colleges as c',
                'c.id',
                '=',
                'cpo.college_id'
            )
            ->where(
                'c.university_id',
                $universityId
            )
            ->count();
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

    private function countCollegeAdmissionInterviewsForUniversity(int $universityId): int
    {
        if (! Schema::hasTable('college_admission_interviews') || ! Schema::hasTable('college_admission_applications')) return 0;
        return DB::table('college_admission_interviews as i')->join('college_admission_applications as a','a.id','=','i.college_admission_application_id')->join('colleges as c','c.id','=','a.college_id')->where('c.university_id',$universityId)->count();
    }

    private function countCollegeAdmissionScoresForUniversity(int $universityId): int
    {
        if (! Schema::hasTable('college_admission_scores') || ! Schema::hasTable('college_admission_applications')) return 0;
        return DB::table('college_admission_scores as s')
            ->join('college_admission_applications as a','a.id','=','s.college_admission_application_id')
            ->join('colleges as c','c.id','=','a.college_id')
            ->where('c.university_id',$universityId)->count();
    }

    private function countCollegeAdmissionApplicationsForUniversity(int $universityId): int
    {
        if (! Schema::hasTable('college_admission_applications')) {
            return 0;
        }
        return DB::table('college_admission_applications as a')
            ->join('colleges as c', 'c.id', '=', 'a.college_id')
            ->where('c.university_id', $universityId)
            ->count();
    }

    private function countCollegeAdmissionApplicationChoicesForUniversity(int $universityId): int
    {
        if (! Schema::hasTable('college_admission_application_choices') || ! Schema::hasTable('college_admission_applications')) {
            return 0;
        }
        return DB::table('college_admission_application_choices as ch')
            ->join('college_admission_applications as a', 'a.id', '=', 'ch.college_admission_application_id')
            ->join('colleges as c', 'c.id', '=', 'a.college_id')
            ->where('c.university_id', $universityId)
            ->count();
    }

    private function countCollegeAdmissionCyclesForUniversity(int $universityId): int
    {
        if (! Schema::hasTable('college_admission_cycles')) {
            return 0;
        }
        return DB::table('college_admission_cycles as ac')
            ->join('colleges as c', 'c.id', '=', 'ac.college_id')
            ->where('c.university_id', $universityId)
            ->count();
    }

    private function countCollegeAdmissionSelectionRulesForUniversity(int $universityId): int
    {
        if (! Schema::hasTable('college_admission_selection_rules')) {
            return 0;
        }
        return DB::table('college_admission_selection_rules as sr')
            ->join('college_program_intakes as i', 'i.id', '=', 'sr.college_program_intake_id')
            ->join('college_program_offerings as o', 'o.id', '=', 'i.college_program_offering_id')
            ->join('colleges as c', 'c.id', '=', 'o.college_id')
            ->where('c.university_id', $universityId)
            ->count();
    }

    private function countCollegeAdmissionSelectionRuleTieBreakersForUniversity(int $universityId): int
    {
        if (! Schema::hasTable('college_admission_selection_rule_tiebreakers') || ! Schema::hasTable('college_admission_selection_rules')) {
            return 0;
        }
        return DB::table('college_admission_selection_rule_tiebreakers as tb')
            ->join('college_admission_selection_rules as sr', 'sr.id', '=', 'tb.college_admission_selection_rule_id')
            ->join('college_program_intakes as i', 'i.id', '=', 'sr.college_program_intake_id')
            ->join('college_program_offerings as o', 'o.id', '=', 'i.college_program_offering_id')
            ->join('colleges as c', 'c.id', '=', 'o.college_id')
            ->where('c.university_id', $universityId)
            ->count();
    }

}
