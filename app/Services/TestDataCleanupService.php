<?php

namespace App\Services;

use App\Models\AcademicPolicy;
use App\Models\Curriculum;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
        ['college_admission_application_academic_preferences', 'curriculum_id'],
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
            'downstream_references' => array_merge(
                $this->downstreamReferences($curriculum->id, self::CURRICULUM_DOWNSTREAM_REFERENCES),
                (Schema::hasTable('academic_calendar_term_periods') && DB::table('academic_calendar_term_periods')->whereIn('curriculum_term_id', $termIds)->exists())
                    ? [['table' => 'academic_calendar_term_periods', 'count' => DB::table('academic_calendar_term_periods')->whereIn('curriculum_term_id', $termIds)->count()]]
                    : []
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


    public function legacyUnlinkedRegularApplicationsPreview(int $universityId): array
    {
        if (! Schema::hasTable('college_admission_applications')) {
            return [
                'confirmation_code' => 'CLEAN-UNLINKED-REGULAR-APPLICATIONS',
                'total' => 0,
                'cleanable' => 0,
                'blocked' => 0,
            ];
        }

        $rows = DB::table('college_admission_applications as a')
            ->join('colleges as c', 'c.id', '=', 'a.college_id')
            ->where('c.university_id', $universityId)
            ->where('a.admission_mode', 'REGULAR')
            ->where('a.status', 'SUBMITTED')
            ->when(
                Schema::hasColumn('college_admission_applications', 'entry_source'),
                fn ($query) => $query->where('a.entry_source', 'PUBLIC')
            )
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('college_admission_application_choices as ch')
                    ->whereColumn('ch.college_admission_application_id', 'a.id');
            })
            ->pluck('a.id');

        $blocked = $rows->filter(function ($id) {
            return count($this->downstreamReferences(
                (int) $id,
                [
                    ['college_admission_scores', 'college_admission_application_id'],
                    ['college_admission_interviews', 'college_admission_application_id'],
                    ['college_admission_merit_entries', 'college_admission_application_id'],
                    ['college_admission_seat_allocations', 'college_admission_application_id'],
                    ['admissions', 'college_admission_application_id'],
                    ['students', 'college_admission_application_id'],
                ]
            )) > 0;
        })->count();

        return [
            'confirmation_code' => 'CLEAN-UNLINKED-REGULAR-APPLICATIONS',
            'total' => $rows->count(),
            'cleanable' => $rows->count() - $blocked,
            'blocked' => $blocked,
        ];
    }

    public function cleanupLegacyUnlinkedRegularApplications(
        int $universityId,
        int $actorId
    ): array {
        $this->assertCleanupEnabled();

        if (! Schema::hasTable('college_admission_applications')) {
            return ['deleted' => 0, 'blocked' => 0];
        }

        $candidateIds = DB::table('college_admission_applications as a')
            ->join('colleges as c', 'c.id', '=', 'a.college_id')
            ->where('c.university_id', $universityId)
            ->where('a.admission_mode', 'REGULAR')
            ->where('a.status', 'SUBMITTED')
            ->when(
                Schema::hasColumn('college_admission_applications', 'entry_source'),
                fn ($query) => $query->where('a.entry_source', 'PUBLIC')
            )
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('college_admission_application_choices as ch')
                    ->whereColumn('ch.college_admission_application_id', 'a.id');
            })
            ->pluck('a.id');

        $cleanableIds = $candidateIds->reject(function ($id) {
            return count($this->downstreamReferences(
                (int) $id,
                [
                    ['college_admission_scores', 'college_admission_application_id'],
                    ['college_admission_interviews', 'college_admission_application_id'],
                    ['college_admission_merit_entries', 'college_admission_application_id'],
                    ['college_admission_seat_allocations', 'college_admission_application_id'],
                    ['admissions', 'college_admission_application_id'],
                    ['students', 'college_admission_application_id'],
                ]
            )) > 0;
        })->values();

        $blocked = $candidateIds->count() - $cleanableIds->count();

        return DB::transaction(function () use ($cleanableIds, $blocked, $actorId, $universityId) {
            $this->deleteWhereIn('college_admission_application_field_values', 'college_admission_application_id', $cleanableIds);
            $this->deleteWhereIn('college_admission_application_course_choices', 'college_admission_application_id', $cleanableIds);
            $this->deleteWhereIn('college_admission_application_academic_preferences', 'college_admission_application_id', $cleanableIds);
            $this->deleteWhereIn('college_admission_application_choices', 'college_admission_application_id', $cleanableIds);

            $deleted = 0;
            if ($cleanableIds->isNotEmpty()) {
                $deleted = DB::table('college_admission_applications')
                    ->whereIn('id', $cleanableIds)
                    ->delete();
            }

            $result = [
                'deleted' => $deleted,
                'blocked' => $blocked,
                'preserved' => [
                    'Applicant users/login identities',
                    'Applicant profiles/registration numbers',
                ],
            ];

            $this->audit(
                'TEST_LEGACY_UNLINKED_REGULAR_APPLICATIONS_CLEANED',
                'test_data_cleanup',
                $universityId,
                $result,
                $actorId
            );

            return $result;
        });
    }

    public function accessResetPreview(
        int $universityId,
        int $currentUserId
    ): array {
        $users = collect($this->accessUserRows($universityId, $currentUserId));
        $roles = collect($this->accessRoleRows($universityId));

        return [
            'confirmation_code' => 'RESET-ACCESS-TEST-DATA',
            'users_total' => $users->count(),
            'users_cleanable' => $users->where('blocked', false)->count(),
            'users_protected_or_blocked' => $users->where('blocked', true)->count(),
            'roles_total' => $roles->count(),
            'roles_cleanable' => $roles->where('blocked', false)->count(),
            'roles_protected_or_blocked' => $roles->where('blocked', true)->count(),
            'preserved' => [
                'Current logged-in cleanup user',
                'SUPER_ADMIN / system-role identities',
                'Applicant login identities',
                'System roles',
                'Permission catalog',
                'Audit logs',
                'Users or custom roles with operational RESTRICT references',
            ],
        ];
    }

    public function fullAccessReset(
        int $universityId,
        int $actorId
    ): array {
        $this->assertCleanupEnabled();

        return DB::transaction(function () use ($universityId, $actorId) {
            $usersDeleted = 0;
            $rolesDeleted = 0;

            foreach ($this->accessUserRows($universityId, $actorId) as $user) {
                if ($user['blocked']) {
                    continue;
                }

                $this->cleanupAccessUser(
                    (int) $user['id'],
                    $universityId,
                    $actorId,
                    false
                );
                $usersDeleted++;
            }

            // Recompute after user cleanup because user-role assignments may have
            // disappeared and can change the dependency picture for custom roles.
            foreach ($this->accessRoleRows($universityId) as $role) {
                if ($role['blocked']) {
                    continue;
                }

                $this->cleanupAccessRole(
                    (int) $role['id'],
                    $universityId,
                    $actorId,
                    false
                );
                $rolesDeleted++;
            }

            $result = [
                'users_deleted' => $usersDeleted,
                'roles_deleted' => $rolesDeleted,
            ];

            $this->audit(
                'TEST_ACCESS_DATA_FULL_RESET',
                'test_data_cleanup',
                $universityId,
                $result,
                $actorId
            );

            return $result;
        });
    }

    public function listMaintenanceEntities(
        int $universityId
    ): array {
        return [
            'users' => $this->accessUserRows($universityId, auth()->id() ?? 0),
            'roles' => $this->accessRoleRows($universityId),
            'applicants' => $this->applicantRows($universityId),
            'college_admission_form_templates' =>
                $this->collegeAdmissionFormTemplateRows($universityId),
            'college_application_fee_rules' =>
                $this->collegeApplicationFeeRuleRows($universityId),
            'fee_payments' =>
                $this->feePaymentRows($universityId),
            'gateway_test_orders' =>
                $this->gatewayTestOrderRows($universityId),
            'fee_late_fine_charges' =>
                $this->feeLateFineChargeRows($universityId),
            'fee_installment_schedules' =>
                $this->feeInstallmentScheduleRows($universityId),
            'fee_student_benefits' =>
                $this->feeStudentBenefitRows($universityId),
            'fee_demands' =>
                $this->feeDemandRows($universityId),
            'fee_scholarship_schemes' =>
                $this->feeScholarshipSchemeRows($universityId),
            'fee_structures' =>
                $this->feeStructureRows($universityId),
            'fee_heads' =>
                $this->feeHeadRows($universityId),
            'fee_categories' =>
                $this->feeCategoryRows($universityId),
            'college_admission_document_verifications' =>
                $this->collegeAdmissionDocumentVerificationRows($universityId),
            'college_admission_seat_allocations' =>
                $this->collegeAdmissionSeatAllocationRows($universityId),
            'admissions' =>
                $this->admissionRows($universityId),
            'college_admission_scores' =>
                $this->collegeAdmissionScoreRows($universityId),
            'college_admission_merit_rosters' =>
                $this->collegeAdmissionMeritRosterRows($universityId),
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
            'sections' =>
                $this->sectionRows($universityId),
            'college_academic_calendars' =>
                $this->collegeAcademicCalendarRows($universityId),
            'batches' =>
                $this->batchRows($universityId),
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
                'college_admission_application_academic_preferences' =>
                    $this->countCollegeAdmissionApplicationChildForUniversity('college_admission_application_academic_preferences', $universityId),
                'college_admission_application_course_choices' =>
                    $this->countCollegeAdmissionApplicationChildForUniversity('college_admission_application_course_choices', $universityId),
                'college_applicant_registration_settings' =>
                    $this->countCollegeScopedRowsForUniversity('college_applicant_registration_settings', $universityId),
                'college_admission_interviews' =>
                    $this->countCollegeAdmissionInterviewsForUniversity($universityId),
                'college_admission_document_verification_items' =>
                    $this->countCollegeAdmissionDocumentVerificationItemsForUniversity($universityId),
                'college_admission_document_verifications' =>
                    $this->countCollegeScopedRowsForUniversity('college_admission_document_verifications', $universityId),
                'college_admission_seat_allocations' =>
                    $this->countCollegeScopedRowsForUniversity('college_admission_seat_allocations', $universityId),
                'fee_demand_items' =>
                    $this->countFeeDemandItemsForUniversity($universityId),
                'fee_demands' =>
                    $this->countUniversityRows('fee_demands', $universityId),
                'fee_scholarship_scheme_heads' =>
                    $this->countFeeScholarshipMappingsForUniversity('fee_scholarship_scheme_heads', $universityId),
                'fee_scholarship_scheme_categories' =>
                    $this->countFeeScholarshipMappingsForUniversity('fee_scholarship_scheme_categories', $universityId),
                'fee_scholarship_schemes' =>
                    $this->countUniversityRows('fee_scholarship_schemes', $universityId),
                'admissions' =>
                    $this->countCollegeScopedRowsForUniversity('admissions', $universityId),
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
                'sections' =>
                    $this->countSectionsForUniversity($universityId),
                'college_academic_calendars' =>
                    $this->countCollegeAcademicCalendarsForUniversity($universityId),
                'college_calendar_overrides' =>
                    $this->countCollegeCalendarOverridesForUniversity($universityId),
                'batches' =>
                    $this->countBatchesForUniversity($universityId),
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
                'Applicant login identities/profiles (applications are cleared, identities are preserved)',
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

            // Online gateway attempts/orders are transactional QA data. Credentials and
            // Fee Head routing are configuration and are intentionally preserved.
            if (Schema::hasTable('online_payment_transactions')) {
                DB::table('online_payment_transactions')
                    ->where('university_id', $universityId)
                    ->delete();
            }

            // Posted test collections are leaf finance transactions. Remove allocations first,
            // then receipts/payments before installments, late fines, benefits and Fee Demands.
            if (Schema::hasTable('fee_payments')) {
                $paymentIds = DB::table('fee_payments')->where('university_id', $universityId)->pluck('id');
                $this->deleteWhereIn('fee_payment_allocations', 'fee_payment_id', $paymentIds);
                $this->deleteWhereIn('fee_payments', 'id', $paymentIds);
            }

            // Student financial-benefit transactions must be removed before their Scheme / Demand parents.
            if (Schema::hasTable('fee_student_benefits')) {
                $benefitIds = DB::table('fee_student_benefits')->where('university_id', $universityId)->pluck('id');
                $this->deleteWhereIn('fee_student_benefit_items', 'fee_student_benefit_id', $benefitIds);
                $this->deleteWhereIn('fee_student_benefits', 'id', $benefitIds);
            }

            // Late Fine Rules are fee-policy test configuration. Full Academic Reset removes them;
            // ordinary Late Fine Charge cleanup preserves the Rule master by default.
            if (Schema::hasTable('fee_late_fine_rules')) {
                $lateFineRuleIds = DB::table('fee_late_fine_rules')->where('university_id', $universityId)->pluck('id');
                if (Schema::hasTable('fee_late_fine_charges')) {
                    $this->deleteWhereIn('fee_late_fine_charges', 'fee_late_fine_rule_id', $lateFineRuleIds);
                }
                $this->deleteWhereIn('fee_late_fine_rules', 'id', $lateFineRuleIds);
            }

            // Scholarship / Concession / Waiver is fee setup test data.
            // Remove mappings + schemes before Reservation Categories / Fee Heads can be cleaned later.
            // This deliberately preserves the referenced Fee Heads and Reservation Categories themselves.
            if (Schema::hasTable('fee_scholarship_schemes')) {
                $scholarshipSchemeIds = DB::table('fee_scholarship_schemes')
                    ->where('university_id', $universityId)
                    ->pluck('id');
                $this->deleteWhereIn('fee_scholarship_scheme_heads', 'fee_scholarship_scheme_id', $scholarshipSchemeIds);
                $this->deleteWhereIn('fee_scholarship_scheme_categories', 'fee_scholarship_scheme_id', $scholarshipSchemeIds);
                $this->deleteWhereIn('fee_scholarship_schemes', 'id', $scholarshipSchemeIds);
            }

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
                // Seat Allocation is downstream of Merit and RESTRICT-links the consumed
                // Merit/Application/Choice/Score rows. Remove Horizontal child rows first,
                // then seat allocations, then the immutable Merit roster.
                $seatAllocationIds = Schema::hasTable('college_admission_seat_allocations')
                    ? DB::table('college_admission_seat_allocations')->whereIn('college_admission_application_id', $applicationIds)->pluck('id')
                    : collect();
                $admissionIds = Schema::hasTable('admissions')
                    ? DB::table('admissions')->whereIn('college_admission_application_id', $applicationIds)->pluck('id')
                    : collect();
                $feeDemandIds = Schema::hasTable('fee_demands') && $admissionIds->isNotEmpty()
                    ? DB::table('fee_demands')->whereIn('admission_id', $admissionIds)->pluck('id')
                    : collect();
                $this->deleteWhereIn('fee_demand_items', 'fee_demand_id', $feeDemandIds);
                $this->deleteWhereIn('fee_demands', 'id', $feeDemandIds);


                $this->deleteWhereIn('admissions', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_seat_allocation_horizontal_categories', 'college_admission_seat_allocation_id', $seatAllocationIds);
                $this->deleteWhereIn('college_admission_seat_allocations', 'college_admission_application_id', $applicationIds);
                $verificationIds = Schema::hasTable('college_admission_document_verifications')
                    ? DB::table('college_admission_document_verifications')->whereIn('college_admission_application_id', $applicationIds)->pluck('id')
                    : collect();
                $this->deleteWhereIn('college_admission_document_verification_items', 'college_admission_document_verification_id', $verificationIds);
                $this->deleteWhereIn('college_admission_document_verifications', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_merit_entries', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_interview_evaluators', 'college_admission_interview_id', $interviewIds);
                $this->deleteWhereIn('college_admission_interviews', 'id', $interviewIds);
                $this->deleteWhereIn('college_admission_scores', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_application_field_values', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_application_course_choices', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_application_academic_preferences', 'college_admission_application_id', $applicationIds);
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
                    $this->deleteAdmissionFormFieldReferences($fieldIdsForCleanup);

                    // Break parent inheritance links before deleting the template tree.
                    DB::table('college_admission_form_templates')->where('university_id', $universityId)->update(['parent_template_id' => null]);
                    DB::table('college_admission_form_templates')->where('university_id', $universityId)->delete();
                }

                if (Schema::hasTable('college_applicant_registration_settings')) {
                    DB::table('college_applicant_registration_settings')
                        ->whereIn('college_id', $collegeIds)
                        ->delete();
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

            // 4) College operational setup: sections -> batches -> allocations -> intake -> offering.
            if (Schema::hasTable('batches') && Schema::hasTable('college_program_offerings') && $collegeIds->isNotEmpty()) {
                $batchOfferingIds = DB::table('college_program_offerings')
                    ->whereIn('college_id', $collegeIds)
                    ->pluck('id');
                $batchIdsForReset = DB::table('batches')->whereIn('college_program_offering_id', $batchOfferingIds)->pluck('id');
                $this->deleteWhereIn('sections', 'batch_id', $batchIdsForReset);
                DB::table('batches')->whereIn('college_program_offering_id', $batchOfferingIds)->delete();
            }

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

            // College Calendar overrides/adoptions must be removed before their University Calendar parents.
            if (Schema::hasTable('college_academic_calendars') && $collegeIds->isNotEmpty()) {
                $collegeCalendarIds = DB::table('college_academic_calendars')->whereIn('college_id', $collegeIds)->pluck('id');
                $this->deleteWhereIn('college_calendar_overrides', 'college_academic_calendar_id', $collegeCalendarIds);
                $this->deleteWhereIn('college_academic_calendars', 'id', $collegeCalendarIds);
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

            // 5) Curriculum structure before Curriculum headers. Calendar-term mappings are test/config dependencies and must be removed first in full reset.
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

            $this->deleteWhereIn('academic_calendar_term_periods', 'curriculum_term_id', $termIds);
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


    public function deactivateAdmissionFormTemplateForTesting(
        int $id,
        int $universityId,
        int $actorId
    ): array {
        $this->assertCleanupEnabled();

        if (! Schema::hasTable('college_admission_form_templates')) {
            abort(404);
        }

        return DB::transaction(function () use ($id, $universityId, $actorId) {
            $record = DB::table('college_admission_form_templates')
                ->where('id', $id)
                ->where('university_id', $universityId)
                ->lockForUpdate()
                ->first();

            if (! $record) {
                abort(404);
            }

            if (strtoupper(trim((string) $record->status)) !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'record' =>
                        'Only an ACTIVE Admission Form Template can be returned to DRAFT through Test Data Cleanup.',
                ]);
            }

            $before = (array) $record;
            $disabledPublicMappings = 0;

            if (Schema::hasTable('college_admission_form_mappings')) {
                $mappingQuery = DB::table('college_admission_form_mappings')
                    ->where('college_admission_form_template_id', $id);

                if (Schema::hasColumn('college_admission_form_mappings', 'public_enabled')) {
                    $disabledPublicMappings = (clone $mappingQuery)
                        ->where('public_enabled', true)
                        ->count();

                    $updates = [
                        'public_enabled' => false,
                    ];

                    if (Schema::hasColumn('college_admission_form_mappings', 'public_enabled_at')) {
                        $updates['public_enabled_at'] = null;
                    }

                    if (Schema::hasColumn('college_admission_form_mappings', 'updated_at')) {
                        $updates['updated_at'] = now();
                    }

                    $mappingQuery->update($updates);
                }
            }

            $templateUpdates = [
                'status' => 'DRAFT',
            ];

            if (Schema::hasColumn('college_admission_form_templates', 'updated_at')) {
                $templateUpdates['updated_at'] = now();
            }

            DB::table('college_admission_form_templates')
                ->where('id', $id)
                ->where('university_id', $universityId)
                ->update($templateUpdates);

            $after = (array) DB::table('college_admission_form_templates')
                ->where('id', $id)
                ->where('university_id', $universityId)
                ->first();

            $after['disabled_public_mappings'] = $disabledPublicMappings;

            $this->audit(
                'TEST_ADMISSION_FORM_TEMPLATE_DEACTIVATED',
                'test_data_cleanup',
                $id,
                $before,
                $actorId,
                $after
            );

            return [
                'template_id' => $id,
                'from_status' => 'ACTIVE',
                'to_status' => 'DRAFT',
                'disabled_public_mappings' => $disabledPublicMappings,
            ];
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
            'users' =>
                $this->cleanupAccessUser($id, $universityId, $actorId),
            'roles' =>
                $this->cleanupAccessRole($id, $universityId, $actorId),
            'applicants' =>
                $this->cleanupApplicant($id, $universityId, $actorId),
            'college_admission_form_templates' =>
                $this->cleanupCollegeAdmissionFormTemplate($id, $universityId, $actorId),
            'college_application_fee_rules' =>
                $this->cleanupCollegeApplicationFeeRule($id, $universityId, $actorId),
            'fee_payments' =>
                $this->cleanupFeePayment($id, $universityId, $actorId),
            'gateway_test_orders' =>
                $this->cleanupGatewayTestOrder($id, $universityId, $actorId),
            'fee_late_fine_charges' =>
                $this->cleanupFeeLateFineCharge($id, $universityId, $actorId),
            'fee_installment_schedules' =>
                $this->cleanupFeeInstallmentSchedule($id, $universityId, $actorId),
            'fee_student_benefits' =>
                $this->cleanupFeeStudentBenefit($id, $universityId, $actorId),
            'fee_demands' =>
                $this->cleanupFeeDemand($id, $universityId, $actorId),
            'fee_scholarship_schemes' =>
                $this->cleanupFeeScholarshipScheme($id, $universityId, $actorId),
            'fee_structures' =>
                $this->cleanupFeeStructure($id, $universityId, $actorId),
            'fee_heads' =>
                $this->cleanupFeeHead($id, $universityId, $actorId),
            'fee_categories' =>
                $this->cleanupFeeCategory($id, $universityId, $actorId),
            'college_admission_document_verifications' =>
                $this->cleanupCollegeAdmissionDocumentVerification($id, $universityId, $actorId),
            'college_admission_seat_allocations' =>
                $this->cleanupCollegeAdmissionSeatAllocation($id, $universityId, $actorId),
            'admissions' =>
                $this->cleanupAdmission($id, $universityId, $actorId),
            'college_admission_scores' =>
                $this->cleanupCollegeAdmissionScore($id, $universityId, $actorId),
            'college_admission_merit_rosters' =>
                $this->cleanupCollegeAdmissionMeritRoster($id, $universityId, $actorId),
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
            'sections' =>
                $this->cleanupSection($id, $universityId, $actorId),
            'college_academic_calendars' =>
                $this->cleanupCollegeAcademicCalendar($id, $universityId, $actorId),
            'batches' =>
                $this->cleanupBatch($id, $universityId, $actorId),
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

    private function gatewayTestOrderRows(int $universityId): array
    {
        if (! Schema::hasTable('online_payment_transactions')) {
            return [];
        }

        return DB::table('online_payment_transactions as opt')
            ->leftJoin('colleges as c', 'c.id', '=', 'opt.college_id')
            ->leftJoin('college_payment_gateways as cpg', 'cpg.id', '=', 'opt.college_payment_gateway_id')
            ->where('opt.university_id', $universityId)
            ->where('opt.purpose', 'CREDENTIAL_TEST_ORDER')
            ->orderByDesc('opt.id')
            ->get([
                'opt.id',
                'opt.reference_no',
                'opt.provider',
                'opt.environment',
                'opt.amount',
                'opt.currency',
                'opt.provider_order_id',
                'opt.provider_status',
                'opt.status',
                'c.name as college_name',
                'cpg.display_name as gateway_name',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'code' => (string) $row->reference_no,
                'name' => trim(($row->gateway_name ?: $row->provider).' · '.($row->college_name ?: 'College').' · '.number_format((float) $row->amount, 2).' '.($row->currency ?: 'INR')),
                'status' => $row->status,
                'kind' => 'GATEWAY_TEST_ORDER',
                'dependencies' => [
                    'provider' => $row->provider,
                    'environment' => $row->environment,
                    'provider_order_id' => $row->provider_order_id,
                    'provider_status' => $row->provider_status,
                ],
                'blocked' => false,
                'blocking_references' => [],
            ])
            ->values()
            ->all();
    }

    private function cleanupGatewayTestOrder(
        int $id,
        int $universityId,
        int $actorId
    ): array {
        if (! Schema::hasTable('online_payment_transactions')) {
            abort(404);
        }

        return DB::transaction(function () use ($id, $universityId, $actorId) {
            $transaction = DB::table('online_payment_transactions')
                ->where('id', $id)
                ->where('university_id', $universityId)
                ->where('purpose', 'CREDENTIAL_TEST_ORDER')
                ->lockForUpdate()
                ->first();

            if (! $transaction) {
                abort(404);
            }

            DB::table('online_payment_transactions')
                ->where('id', $transaction->id)
                ->delete();

            $this->audit(
                'TEST_GATEWAY_ORDER_CLEANED',
                'online_payment_transaction',
                (int) $transaction->id,
                (array) $transaction,
                $actorId
            );

            return [
                'online_payment_transaction_id' => (int) $transaction->id,
                'provider' => $transaction->provider,
                'reference_no' => $transaction->reference_no,
            ];
        });
    }

    private function countFeeDemandItemsForUniversity(int $universityId): int
    {
        if (! Schema::hasTable('fee_demand_items') || ! Schema::hasTable('fee_demands')) {
            return 0;
        }

        return DB::table('fee_demand_items as fdi')
            ->join('fee_demands as fd', 'fd.id', '=', 'fdi.fee_demand_id')
            ->where('fd.university_id', $universityId)
            ->count();
    }

    private function feePaymentRows(int $universityId): array
    {
        if (! Schema::hasTable('fee_payments')) return [];
        return DB::table('fee_payments as p')
            ->join('colleges as c','c.id','=','p.college_id')
            ->join('admissions as ad','ad.id','=','p.admission_id')
            ->leftJoin('college_admission_applications as a','a.id','=','ad.college_admission_application_id')
            ->where('p.university_id',$universityId)->where('p.status','POSTED')->orderByDesc('p.id')
            ->get(['p.id','p.receipt_no','p.payment_date','p.amount','p.payment_mode','p.reference_no','c.name as college_name','ad.admission_no','a.candidate_name'])
            ->map(function($r){
                $alloc=$this->countIfExists('fee_payment_allocations','fee_payment_id',(int)$r->id);
                return ['id'=>(int)$r->id,'code'=>$r->receipt_no,'name'=>($r->candidate_name?:$r->admission_no).' · '.$r->college_name.' · '.number_format((float)$r->amount,2),
                    'status'=>'POSTED','kind'=>'FEE_PAYMENT','dependencies'=>['allocations'=>$alloc,'mode'=>$r->payment_mode],
                    'blocked'=>false,'blocking_references'=>[]];
            })->values()->all();
    }

    private function cleanupFeePayment(int $id, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('fee_payments')) abort(404);
        return DB::transaction(function() use($id,$universityId,$actorId){
            $payment=DB::table('fee_payments')->where('id',$id)->where('university_id',$universityId)->where('status','POSTED')->lockForUpdate()->first();
            if(!$payment) abort(404);
            $allocations=DB::table('fee_payment_allocations')->where('fee_payment_id',$payment->id)->orderBy('sequence_no')->lockForUpdate()->get();
            $demandIds=$allocations->pluck('fee_demand_id')->unique()->values();
            foreach($allocations->where('source_type','INSTALLMENT') as $a){
                if($a->fee_installment_schedule_id){
                    $schedule=DB::table('fee_installment_schedules')->where('id',$a->fee_installment_schedule_id)->lockForUpdate()->first();
                    if($schedule) DB::table('fee_installment_schedules')->where('id',$schedule->id)->update(['paid_amount'=>max(0,round((float)$schedule->paid_amount-(float)$a->amount,2)),'updated_at'=>now()]);
                }
            }
            DB::table('fee_payment_allocations')->where('fee_payment_id',$payment->id)->delete();
            DB::table('fee_payments')->where('id',$payment->id)->delete();
            foreach($demandIds as $demandId){
                $d=DB::table('fee_demands')->where('id',$demandId)->lockForUpdate()->first(); if(!$d)continue;
                $principal=(float)DB::table('fee_payment_allocations as a')->join('fee_payments as p','p.id','=','a.fee_payment_id')
                    ->where('a.fee_demand_id',$demandId)->whereIn('a.source_type',['DEMAND_ITEM','INSTALLMENT'])->where('p.status','POSTED')->sum('a.amount');
                $out=max(round((float)$d->total_amount-$principal-(float)$d->adjusted_amount,2),0);
                $status=$out<=0?'CLEARED':(($principal+(float)$d->adjusted_amount)>0?'PARTIALLY_CLEARED':'OPEN');
                DB::table('fee_demands')->where('id',$demandId)->update(['paid_amount'=>$principal,'outstanding_amount'=>$out,'status'=>$status,'updated_at'=>now()]);
            }
            $this->audit('TEST_FEE_PAYMENT_CLEANED','fee_payment',$id,['payment'=>(array)$payment,'allocations'=>$allocations->toArray()],$actorId);
            return ['fee_payment_id'=>$id,'allocations_deleted'=>$allocations->count()];
        });
    }

    private function feeLateFineChargeRows(int $universityId): array
    {
        if (! Schema::hasTable('fee_late_fine_charges')) return [];
        return DB::table('fee_late_fine_charges as f')
            ->join('fee_demands as d','d.id','=','f.fee_demand_id')
            ->join('fee_demand_items as i','i.id','=','f.fee_demand_item_id')
            ->join('colleges as c','c.id','=','f.college_id')
            ->where('f.university_id',$universityId)
            ->where('f.status','ACTIVE')
            ->orderByDesc('f.id')
            ->get(['f.id','f.status','f.fine_amount','f.overdue_days','d.demand_no','i.fee_head_name','c.name as college_name'])
            ->map(fn($r)=>[
                'id'=>(int)$r->id,'code'=>$r->demand_no,
                'name'=>$r->fee_head_name.' · '.$r->college_name.' · Fine '.number_format((float)$r->fine_amount,2),
                'status'=>$r->status,'kind'=>'FEE_LATE_FINE_CHARGE',
                'dependencies'=>['overdue_days'=>(int)$r->overdue_days,'fine_amount'=>(float)$r->fine_amount],
                'blocked'=>false,'blocking_references'=>[],
            ])->values()->all();
    }

    private function cleanupFeeLateFineCharge(int $id, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('fee_late_fine_charges')) abort(404);
        return DB::transaction(function() use($id,$universityId,$actorId){
            $record=DB::table('fee_late_fine_charges')->where('id',$id)->where('university_id',$universityId)->first();
            if(!$record) abort(404);
            if (Schema::hasTable('fee_payment_allocations') && DB::table('fee_payment_allocations')->where('fee_late_fine_charge_id',$record->id)->exists()) {
                throw ValidationException::withMessages(['record'=>'This Late Fine Charge has payment allocation. Clean the related test payment first.']);
            }
            $history=DB::table('fee_late_fine_charges')
                ->where('university_id',$universityId)
                ->where('fee_late_fine_rule_id',$record->fee_late_fine_rule_id)
                ->where('fee_installment_schedule_id',$record->fee_installment_schedule_id)
                ->get();
            DB::table('fee_late_fine_charges')
                ->where('university_id',$universityId)
                ->where('fee_late_fine_rule_id',$record->fee_late_fine_rule_id)
                ->where('fee_installment_schedule_id',$record->fee_installment_schedule_id)
                ->delete();
            $this->audit('TEST_FEE_LATE_FINE_CHARGE_CLEANED','fee_late_fine_charge',$id,['history'=>$history->toArray()],$actorId);
            return ['late_fine_charge_id'=>$id,'history_deleted'=>$history->count()];
        });
    }

    private function feeInstallmentScheduleRows(int $universityId): array
    {
        if (! Schema::hasTable('fee_installment_schedules')) return [];
        return DB::table('fee_installment_schedules as s')
            ->join('fee_demands as d','d.id','=','s.fee_demand_id')
            ->join('fee_demand_items as i','i.id','=','s.fee_demand_item_id')
            ->join('colleges as c','c.id','=','d.college_id')
            ->where('d.university_id',$universityId)->where('s.status','ACTIVE')
            ->groupBy('i.id','d.demand_no','i.fee_head_name','c.name')
            ->orderByDesc('i.id')
            ->get(['i.id','d.demand_no','i.fee_head_name','c.name as college_name',DB::raw('COUNT(s.id) as schedule_count'),DB::raw('SUM(s.amount) as schedule_total')])
            ->map(fn($r)=>[
                'id'=>(int)$r->id,'code'=>$r->demand_no,'name'=>$r->fee_head_name.' · '.$r->college_name,
                'status'=>'ACTIVE','kind'=>'FEE_INSTALLMENT_SCHEDULE','dependencies'=>['installments'=>(int)$r->schedule_count,'scheduled_total'=>(float)$r->schedule_total],
                'blocked'=>false,'blocking_references'=>[],
            ])->values()->all();
    }

    private function cleanupFeeInstallmentSchedule(int $demandItemId, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('fee_installment_schedules')) abort(404);
        return DB::transaction(function() use($demandItemId,$universityId,$actorId){
            $valid=DB::table('fee_demand_items as i')->join('fee_demands as d','d.id','=','i.fee_demand_id')->where('i.id',$demandItemId)->where('d.university_id',$universityId)->exists();
            if(!$valid) abort(404);
            $scheduleIds=DB::table('fee_installment_schedules')->where('fee_demand_item_id',$demandItemId)->pluck('id');
            if (Schema::hasTable('fee_payment_allocations') && DB::table('fee_payment_allocations')->whereIn('fee_installment_schedule_id',$scheduleIds)->exists()) {
                throw ValidationException::withMessages(['record'=>'This Installment Schedule has payment allocation. Clean the related test payment first.']);
            }
            $rows=DB::table('fee_installment_schedules')->where('fee_demand_item_id',$demandItemId)->get();
            DB::table('fee_installment_schedules')->where('fee_demand_item_id',$demandItemId)->delete();
            $this->audit('TEST_FEE_INSTALLMENT_SCHEDULE_CLEANED','fee_demand_item',$demandItemId,['schedules'=>$rows->toArray()],$actorId);
            return ['fee_demand_item_id'=>$demandItemId,'schedules_deleted'=>$rows->count()];
        });
    }

    private function feeStudentBenefitRows(int $universityId): array
    {
        if (! Schema::hasTable('fee_student_benefits')) return [];
        return DB::table('fee_student_benefits as b')
            ->join('fee_demands as d','d.id','=','b.fee_demand_id')
            ->join('admissions as ad','ad.id','=','b.admission_id')
            ->leftJoin('college_admission_applications as a','a.id','=','ad.college_admission_application_id')
            ->join('colleges as c','c.id','=','b.college_id')
            ->where('b.university_id',$universityId)
            ->orderByDesc('b.id')
            ->get(['b.id','b.scheme_name_snapshot','b.status','b.sanctioned_amount','d.demand_no','a.candidate_name','ad.admission_no','c.name as college_name'])
            ->map(fn($r)=>[
                'id'=>(int)$r->id,
                'code'=>$r->demand_no,
                'name'=>($r->candidate_name ?: $r->admission_no).' · '.$r->scheme_name_snapshot.' · '.$r->college_name,
                'status'=>$r->status,
                'kind'=>'FEE_STUDENT_BENEFIT',
                'dependencies'=>['benefit_items'=>$this->countIfExists('fee_student_benefit_items','fee_student_benefit_id',(int)$r->id)],
                'blocked'=>false,
                'blocking_references'=>[],
            ])->values()->all();
    }

    private function cleanupFeeStudentBenefit(int $id, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('fee_student_benefits')) abort(404);
        return DB::transaction(function () use ($id,$universityId,$actorId) {
            $record = DB::table('fee_student_benefits')->where('id',$id)->where('university_id',$universityId)->lockForUpdate()->first();
            if (! $record) abort(404);
            if ($record->status === 'APPROVED') {
                $demand = DB::table('fee_demands')->where('id',$record->fee_demand_id)->lockForUpdate()->first();
                if (! $demand) abort(404);
                if ((float)$demand->paid_amount > 0) {
                    throw ValidationException::withMessages(['record'=>'Approved benefit cannot be test-cleaned after payment activity.']);
                }
                $sanctioned = (float)($record->sanctioned_amount ?? 0);
                $adjusted = max(round((float)$demand->adjusted_amount - $sanctioned,2),0);
                $outstanding = max(round((float)$demand->total_amount - (float)$demand->paid_amount - $adjusted,2),0);
                $status = $outstanding <= 0 ? 'CLEARED' : (($adjusted + (float)$demand->paid_amount) > 0 ? 'PARTIALLY_CLEARED' : 'OPEN');
                DB::table('fee_demands')->where('id',$demand->id)->update(['adjusted_amount'=>$adjusted,'outstanding_amount'=>$outstanding,'status'=>$status,'updated_at'=>now()]);
            }
            DB::table('fee_student_benefit_items')->where('fee_student_benefit_id',$record->id)->delete();
            DB::table('fee_student_benefits')->where('id',$record->id)->delete();
            $this->audit('TEST_FEE_STUDENT_BENEFIT_CLEANED','fee_student_benefit',$record->id,(array)$record,$actorId);
            return ['benefit_id'=>(int)$record->id,'status'=>$record->status];
        });
    }

    private function feeDemandRows(int $universityId): array
    {
        if (! Schema::hasTable('fee_demands')) {
            return [];
        }

        return DB::table('fee_demands as fd')
            ->join('colleges as c', 'c.id', '=', 'fd.college_id')
            ->join('admissions as ad', 'ad.id', '=', 'fd.admission_id')
            ->leftJoin('college_admission_applications as a', 'a.id', '=', 'ad.college_admission_application_id')
            ->where('fd.university_id', $universityId)
            ->orderByDesc('fd.id')
            ->get([
                'fd.id', 'fd.demand_no', 'fd.status', 'fd.generation_mode', 'fd.billing_period_no',
                'fd.total_amount', 'fd.paid_amount', 'fd.adjusted_amount', 'fd.outstanding_amount',
                'c.name as college_name', 'ad.admission_no', 'a.candidate_name',
            ])
            ->map(function ($row) {
                $items = $this->countIfExists('fee_demand_items', 'fee_demand_id', $row->id);
                $financialRefs = [];
                foreach ([
                    ['fee_payments', 'fee_demand_id'],
                    ['fee_payment_allocations', 'fee_demand_id'],
                    ['fee_adjustments', 'fee_demand_id'],
                    ['fee_waivers', 'fee_demand_id'],
                    ['fee_scholarship_allocations', 'fee_demand_id'],
                    ['fee_student_benefits', 'fee_demand_id'],
                    ['fee_late_fine_charges', 'fee_demand_id'],
                    ['fee_installment_schedules', 'fee_demand_id'],
                    ['fee_refunds', 'fee_demand_id'],
                ] as [$table, $column]) {
                    $financialRefs = array_merge($financialRefs, $this->downstreamReferences($row->id, [[$table, $column]]));
                }

                $hasFinancialAmounts = ((float) $row->paid_amount > 0) || ((float) $row->adjusted_amount > 0);
                if ($hasFinancialAmounts) {
                    $financialRefs[] = [
                        'table' => 'fee_demands',
                        'column' => 'financial_activity',
                        'count' => 1,
                    ];
                }

                return [
                    'id' => (int) $row->id,
                    'code' => $row->demand_no,
                    'name' => ($row->candidate_name ?: $row->admission_no).' · '.$row->college_name.' · Period '.$row->billing_period_no,
                    'status' => $row->status,
                    'kind' => 'FEE_DEMAND',
                    'dependencies' => [
                        'demand_items' => $items,
                    ],
                    'blocked' => count($financialRefs) > 0,
                    'blocking_references' => $financialRefs,
                ];
            })
            ->values()
            ->all();
    }

    private function cleanupFeeDemand(int $id, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('fee_demands')) {
            abort(404);
        }

        $record = DB::table('fee_demands')
            ->where('id', $id)
            ->where('university_id', $universityId)
            ->first();

        if (! $record) {
            abort(404);
        }

        $financialRefs = [];
        foreach ([
            ['fee_payments', 'fee_demand_id'],
            ['fee_payment_allocations', 'fee_demand_id'],
            ['fee_adjustments', 'fee_demand_id'],
            ['fee_waivers', 'fee_demand_id'],
            ['fee_scholarship_allocations', 'fee_demand_id'],
            ['fee_student_benefits', 'fee_demand_id'],
            ['fee_late_fine_charges', 'fee_demand_id'],
            ['fee_installment_schedules', 'fee_demand_id'],
            ['fee_refunds', 'fee_demand_id'],
        ] as [$table, $column]) {
            $financialRefs = array_merge($financialRefs, $this->downstreamReferences($record->id, [[$table, $column]]));
        }

        if (((float) $record->paid_amount > 0) || ((float) $record->adjusted_amount > 0)) {
            $financialRefs[] = [
                'table' => 'fee_demands',
                'column' => 'financial_activity',
                'count' => 1,
            ];
        }

        if (count($financialRefs) > 0) {
            throw ValidationException::withMessages([
                'record' => 'This Fee Demand has payment/adjustment or downstream financial activity and cannot be removed by Test Data Cleanup. Reverse/clean the dependent financial test records first.',
            ]);
        }

        return DB::transaction(function () use ($record, $actorId) {
            $itemCount = $this->countIfExists('fee_demand_items', 'fee_demand_id', $record->id);
            if (Schema::hasTable('fee_demand_items')) {
                DB::table('fee_demand_items')->where('fee_demand_id', $record->id)->delete();
            }
            DB::table('fee_demands')->where('id', $record->id)->delete();

            $result = [
                'record' => (array) $record,
                'deleted_demand_items' => $itemCount,
            ];
            $this->audit('TEST_FEE_DEMAND_CLEANED', 'test_data_cleanup', $record->id, $result, $actorId);
            return $result;
        });
    }

    private function countFeeScholarshipMappingsForUniversity(string $table, int $universityId): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasTable('fee_scholarship_schemes')) {
            return 0;
        }

        return DB::table($table.' as mapping')
            ->join('fee_scholarship_schemes as scheme', 'scheme.id', '=', 'mapping.fee_scholarship_scheme_id')
            ->where('scheme.university_id', $universityId)
            ->count();
    }

    private function feeScholarshipSchemeRows(int $universityId): array
    {
        if (! Schema::hasTable('fee_scholarship_schemes')) {
            return [];
        }

        return DB::table('fee_scholarship_schemes as fss')
            ->leftJoin('colleges as c', 'c.id', '=', 'fss.college_id')
            ->leftJoin('academic_sessions as s', 's.id', '=', 'fss.academic_session_id')
            ->leftJoin('program_templates as pt', 'pt.id', '=', 'fss.program_template_id')
            ->leftJoin('college_program_offerings as cpo', 'cpo.id', '=', 'fss.college_program_offering_id')
            ->leftJoin('program_templates as cpt', 'cpt.id', '=', 'cpo.program_template_id')
            ->where('fss.university_id', $universityId)
            ->orderByRaw('CASE WHEN fss.college_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('c.name')
            ->orderBy('fss.name')
            ->get([
                'fss.id', 'fss.code', 'fss.name', 'fss.status', 'fss.benefit_type', 'fss.calculation_type',
                'fss.benefit_value', 'fss.maximum_benefit_amount', 'fss.eligibility_mode', 'fss.approval_mode',
                'c.name as college_name', 's.name as session_name', 'pt.name as university_program_name',
                'cpt.name as college_program_name',
            ])
            ->map(function ($row) {
                $headMappings = $this->countIfExists('fee_scholarship_scheme_heads', 'fee_scholarship_scheme_id', (int) $row->id);
                $categoryMappings = $this->countIfExists('fee_scholarship_scheme_categories', 'fee_scholarship_scheme_id', (int) $row->id);

                // Future operational scholarship/sanction/adjustment records must block setup cleanup.
                // Missing future tables/columns are safely ignored by downstreamReferences().
                $operationalRefs = $this->downstreamReferences((int) $row->id, [
                    ['fee_scholarship_allocations', 'fee_scholarship_scheme_id'],
                    ['fee_scholarship_allocations', 'scholarship_scheme_id'],
                    ['fee_scholarship_applications', 'fee_scholarship_scheme_id'],
                    ['fee_scholarship_sanctions', 'fee_scholarship_scheme_id'],
                    ['student_fee_benefits', 'fee_scholarship_scheme_id'],
                    ['fee_student_benefits', 'fee_scholarship_scheme_id'],
                    ['fee_adjustments', 'fee_scholarship_scheme_id'],
                ]);

                $scope = $row->college_name
                    ? 'College · '.$row->college_name.' · '.($row->college_program_name ?: 'Program Offering')
                    : 'University · '.($row->university_program_name ?: 'All Programs');

                return [
                    'id' => (int) $row->id,
                    'code' => $row->code,
                    'name' => $scope.' · '.($row->session_name ?: 'Session').' · '.$row->name,
                    'status' => $row->status,
                    'kind' => 'FEE_SCHOLARSHIP_SCHEME',
                    'dependencies' => [
                        'fee_head_mappings' => $headMappings,
                        'reservation_category_mappings' => $categoryMappings,
                    ],
                    'blocked' => count($operationalRefs) > 0,
                    'blocking_references' => $operationalRefs,
                ];
            })
            ->values()
            ->all();
    }

    private function cleanupFeeScholarshipScheme(int $id, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('fee_scholarship_schemes')) {
            abort(404);
        }

        $record = DB::table('fee_scholarship_schemes')
            ->where('id', $id)
            ->where('university_id', $universityId)
            ->first();

        if (! $record) {
            abort(404);
        }

        $operationalRefs = $this->downstreamReferences($id, [
            ['fee_scholarship_allocations', 'fee_scholarship_scheme_id'],
            ['fee_scholarship_allocations', 'scholarship_scheme_id'],
            ['fee_scholarship_applications', 'fee_scholarship_scheme_id'],
            ['fee_scholarship_sanctions', 'fee_scholarship_scheme_id'],
            ['student_fee_benefits', 'fee_scholarship_scheme_id'],
            ['fee_student_benefits', 'fee_scholarship_scheme_id'],
            ['fee_adjustments', 'fee_scholarship_scheme_id'],
        ]);

        if (count($operationalRefs) > 0) {
            throw ValidationException::withMessages([
                'record' => 'This Scholarship / Benefit Scheme is already used by student financial-benefit activity. Clean the downstream test transaction first.',
            ]);
        }

        return DB::transaction(function () use ($record, $actorId) {
            $headMappings = $this->countIfExists('fee_scholarship_scheme_heads', 'fee_scholarship_scheme_id', (int) $record->id);
            $categoryMappings = $this->countIfExists('fee_scholarship_scheme_categories', 'fee_scholarship_scheme_id', (int) $record->id);

            if (Schema::hasTable('fee_scholarship_scheme_heads')) {
                DB::table('fee_scholarship_scheme_heads')
                    ->where('fee_scholarship_scheme_id', $record->id)
                    ->delete();
            }
            if (Schema::hasTable('fee_scholarship_scheme_categories')) {
                DB::table('fee_scholarship_scheme_categories')
                    ->where('fee_scholarship_scheme_id', $record->id)
                    ->delete();
            }
            DB::table('fee_scholarship_schemes')->where('id', $record->id)->delete();

            $result = [
                'record' => (array) $record,
                'deleted_fee_head_mappings' => $headMappings,
                'deleted_reservation_category_mappings' => $categoryMappings,
            ];

            $this->audit(
                'TEST_FEE_SCHOLARSHIP_SCHEME_CLEANED',
                'test_data_cleanup',
                (int) $record->id,
                $result,
                $actorId
            );

            return $result;
        });
    }

    private function feeStructureRows(int $universityId): array
    {
        if (! Schema::hasTable('fee_structures')) {
            return [];
        }

        return DB::table('fee_structures as fs')
            ->leftJoin('colleges as c', 'c.id', '=', 'fs.college_id')
            ->leftJoin('program_templates as pt', 'pt.id', '=', 'fs.program_template_id')
            ->leftJoin('college_program_offerings as cpo', 'cpo.id', '=', 'fs.college_program_offering_id')
            ->leftJoin('program_templates as cpt', 'cpt.id', '=', 'cpo.program_template_id')
            ->join('academic_sessions as s', 's.id', '=', 'fs.academic_session_id')
            ->where('fs.university_id', $universityId)
            ->orderByRaw('CASE WHEN fs.college_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('c.name')
            ->orderBy('fs.name')
            ->get([
                'fs.id', 'fs.code', 'fs.name', 'fs.status', 'fs.purpose', 'fs.college_applicability',
                'c.name as college_name', 'pt.name as university_program_name',
                'cpt.name as college_program_name', 's.name as session_name',
            ])
            ->map(function ($row) {
                $items = $this->countIfExists('fee_structure_items', 'fee_structure_id', $row->id);
                $adoptions = $this->countIfExists('college_fee_structure_adoptions', 'university_fee_structure_id', $row->id);
                $scope = $row->college_name
                    ? 'College · '.$row->college_name.' · '.($row->college_program_name ?: 'Program Offering')
                    : 'University · '.($row->university_program_name ?: 'All Programs');

                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => $scope.' · '.$row->session_name.' · '.$row->name,
                    'status' => $row->status,
                    'kind' => 'FEE_STRUCTURE',
                    'dependencies' => [
                        'fee_items' => $items,
                        'college_adoptions' => $adoptions,
                    ],
                    'blocked' => false,
                    'blocking_references' => [],
                ];
            })
            ->values()
            ->all();
    }

    private function cleanupFeeStructure(int $id, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('fee_structures')) {
            abort(404);
        }

        $record = DB::table('fee_structures')
            ->where('id', $id)
            ->where('university_id', $universityId)
            ->first();

        if (! $record) {
            abort(404);
        }

        // Fee Demand/payment tables are intentionally treated as operational consumers.
        // Once they exist, setup cleanup must never remove a structure referenced by them.
        $operationalRefs = $this->downstreamReferences($id, [
            ['fee_demands', 'fee_structure_id'],
            ['student_fee_demands', 'fee_structure_id'],
        ]);

        if (count($operationalRefs) > 0) {
            throw ValidationException::withMessages([
                'record' => 'This Fee Structure is already referenced by operational Fee Demand data. Clean the downstream test transactions first.',
            ]);
        }

        return DB::transaction(function () use ($record, $actorId) {
            $itemIds = Schema::hasTable('fee_structure_items')
                ? DB::table('fee_structure_items')->where('fee_structure_id', $record->id)->pluck('id')
                : collect();
            $itemCount = $itemIds->count();
            $periodAmountCount = Schema::hasTable('fee_structure_item_period_amounts') && $itemIds->isNotEmpty()
                ? DB::table('fee_structure_item_period_amounts')->whereIn('fee_structure_item_id', $itemIds)->count()
                : 0;
            $periodExclusionCount = Schema::hasTable('fee_structure_item_period_exclusions') && $itemIds->isNotEmpty()
                ? DB::table('fee_structure_item_period_exclusions')->whereIn('fee_structure_item_id', $itemIds)->count()
                : 0;
            $periodSettingCount = Schema::hasTable('fee_structure_item_period_settings') && $itemIds->isNotEmpty()
                ? DB::table('fee_structure_item_period_settings')->whereIn('fee_structure_item_id', $itemIds)->count()
                : 0;
            $adoptionCount = $this->countIfExists('college_fee_structure_adoptions', 'university_fee_structure_id', $record->id);

            if (Schema::hasTable('college_fee_structure_adoptions')) {
                DB::table('college_fee_structure_adoptions')
                    ->where('university_fee_structure_id', $record->id)
                    ->delete();
            }
            if (Schema::hasTable('fee_structure_item_period_settings') && $itemIds->isNotEmpty()) {
                DB::table('fee_structure_item_period_settings')
                    ->whereIn('fee_structure_item_id', $itemIds)
                    ->delete();
            }
            if (Schema::hasTable('fee_structure_item_period_exclusions') && $itemIds->isNotEmpty()) {
                DB::table('fee_structure_item_period_exclusions')
                    ->whereIn('fee_structure_item_id', $itemIds)
                    ->delete();
            }
            if (Schema::hasTable('fee_structure_item_period_amounts') && $itemIds->isNotEmpty()) {
                DB::table('fee_structure_item_period_amounts')
                    ->whereIn('fee_structure_item_id', $itemIds)
                    ->delete();
            }
            if (Schema::hasTable('fee_structure_items')) {
                DB::table('fee_structure_items')
                    ->where('fee_structure_id', $record->id)
                    ->delete();
            }
            DB::table('fee_structures')->where('id', $record->id)->delete();

            $result = [
                'record' => (array) $record,
                'deleted_fee_items' => $itemCount,
                'deleted_period_amount_overrides' => $periodAmountCount,
                'deleted_period_exclusions' => $periodExclusionCount,
                'deleted_period_settings' => $periodSettingCount,
                'deleted_college_adoptions' => $adoptionCount,
            ];
            $this->audit('TEST_FEE_STRUCTURE_CLEANED', 'test_data_cleanup', $record->id, $result, $actorId);
            return $result;
        });
    }

    private function feeHeadRows(int $universityId): array
    {
        if (! Schema::hasTable('fee_heads')) {
            return [];
        }

        return DB::table('fee_heads as fh')
            ->leftJoin('colleges as c', 'c.id', '=', 'fh.college_id')
            ->leftJoin('fee_categories as fc', 'fc.id', '=', 'fh.fee_category_id')
            ->where('fh.university_id', $universityId)
            ->orderByRaw('CASE WHEN fh.college_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('c.name')
            ->orderBy('fh.name')
            ->get(['fh.id', 'fh.code', 'fh.name', 'fh.status', 'c.name as college_name', 'fc.name as category_name'])
            ->map(function ($row) {
                $downstream = $this->downstreamReferences($row->id, [
                    ['fee_structure_items', 'fee_head_id'],
                    ['fee_demand_items', 'fee_head_id'],
                    ['fee_late_fine_rules', 'fee_head_id'],
                    ['student_fee_demand_items', 'fee_head_id'],
                ]);

                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => ($row->college_name ? 'College · '.$row->college_name : 'University').' · '.($row->category_name ?: 'Uncategorised').' · '.$row->name,
                    'status' => $row->status,
                    'kind' => 'FEE_HEAD',
                    'dependencies' => [],
                    'blocked' => count($downstream) > 0,
                    'blocking_references' => $downstream,
                ];
            })
            ->values()
            ->all();
    }

    private function cleanupFeeHead(int $id, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('fee_heads')) {
            abort(404);
        }

        $record = DB::table('fee_heads')
            ->where('id', $id)
            ->where('university_id', $universityId)
            ->first();

        if (! $record) {
            abort(404);
        }

        $downstream = $this->downstreamReferences($id, [
            ['fee_structure_items', 'fee_head_id'],
            ['fee_demand_items', 'fee_head_id'],
            ['fee_late_fine_rules', 'fee_head_id'],
            ['student_fee_demand_items', 'fee_head_id'],
        ]);

        if (count($downstream) > 0) {
            throw ValidationException::withMessages([
                'record' => 'This Fee Head is still used by a Fee Structure or Fee Demand. Clean the dependent test records first.',
            ]);
        }

        DB::table('fee_heads')->where('id', $id)->delete();
        $result = ['record' => (array) $record];
        $this->audit('TEST_FEE_HEAD_CLEANED', 'test_data_cleanup', $id, $result, $actorId);
        return $result;
    }

    private function feeCategoryRows(int $universityId): array
    {
        if (! Schema::hasTable('fee_categories')) {
            return [];
        }

        $protectedUniversityDefaults = [
            'ADMISSION', 'TUITION', 'REGISTRATION', 'EXAMINATION', 'LIBRARY', 'LAB',
            'HOSTEL', 'TRANSPORT', 'DEVELOPMENT', 'CERTIFICATE', 'OTHER',
        ];

        return DB::table('fee_categories as fc')
            ->leftJoin('colleges as c', 'c.id', '=', 'fc.college_id')
            ->where('fc.university_id', $universityId)
            ->orderByRaw('CASE WHEN fc.college_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('c.name')
            ->orderBy('fc.display_order')
            ->orderBy('fc.name')
            ->get(['fc.id', 'fc.code', 'fc.name', 'fc.status', 'fc.college_id', 'c.name as college_name'])
            ->map(function ($row) use ($protectedUniversityDefaults) {
                $downstream = $this->downstreamReferences($row->id, [
                    ['fee_heads', 'fee_category_id'],
                ]);
                $isProtectedDefault = $row->college_id === null && in_array(strtoupper((string) $row->code), $protectedUniversityDefaults, true);
                if ($isProtectedDefault) {
                    $downstream[] = [
                        'table' => 'fee_categories',
                        'column' => 'system_default',
                        'count' => 1,
                    ];
                }

                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => ($row->college_name ? 'College · '.$row->college_name : 'University').' · '.$row->name,
                    'status' => $row->status,
                    'kind' => 'FEE_CATEGORY',
                    'dependencies' => [],
                    'blocked' => count($downstream) > 0,
                    'blocking_references' => $downstream,
                ];
            })
            ->values()
            ->all();
    }

    private function cleanupFeeCategory(int $id, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('fee_categories')) {
            abort(404);
        }

        $record = DB::table('fee_categories')
            ->where('id', $id)
            ->where('university_id', $universityId)
            ->first();

        if (! $record) {
            abort(404);
        }

        $protectedUniversityDefaults = [
            'ADMISSION', 'TUITION', 'REGISTRATION', 'EXAMINATION', 'LIBRARY', 'LAB',
            'HOSTEL', 'TRANSPORT', 'DEVELOPMENT', 'CERTIFICATE', 'OTHER',
        ];
        if ($record->college_id === null && in_array(strtoupper((string) $record->code), $protectedUniversityDefaults, true)) {
            throw ValidationException::withMessages([
                'record' => 'System default University Fee Categories are baseline setup and are not removed by Test Data Cleanup.',
            ]);
        }

        $downstream = $this->downstreamReferences($id, [
            ['fee_heads', 'fee_category_id'],
        ]);
        if (count($downstream) > 0) {
            throw ValidationException::withMessages([
                'record' => 'This Fee Category is still used by a Fee Head. Clean the dependent test Fee Heads first.',
            ]);
        }

        DB::table('fee_categories')->where('id', $id)->delete();
        $result = ['record' => (array) $record];
        $this->audit('TEST_FEE_CATEGORY_CLEANED', 'test_data_cleanup', $id, $result, $actorId);
        return $result;
    }

    private function accessUserRows(
        int $universityId,
        int $currentUserId
    ): array {
        if (! Schema::hasTable('users')) {
            return [];
        }

        $collegeIds = Schema::hasTable('colleges')
            ? DB::table('colleges')
                ->where('university_id', $universityId)
                ->pluck('id')
            : collect();

        $query = DB::table('users as u')
            ->leftJoin('colleges as c', 'c.id', '=', 'u.primary_college_id')
            ->whereIn('u.account_type', ['UNIVERSITY_STAFF', 'COLLEGE_STAFF'])
            ->where(function ($scope) use ($collegeIds) {
                $scope->where('u.account_type', 'UNIVERSITY_STAFF');
                if ($collegeIds->isNotEmpty()) {
                    $scope->orWhere(function ($collegeScope) use ($collegeIds) {
                        $collegeScope
                            ->where('u.account_type', 'COLLEGE_STAFF')
                            ->whereIn('u.primary_college_id', $collegeIds);
                    });
                }
            })
            ->orderBy('u.name')
            ->orderBy('u.id');

        return $query
            ->get([
                'u.id',
                'u.name',
                'u.email',
                'u.account_type',
                'u.status',
                'u.primary_college_id',
                'c.name as college_name',
            ])
            ->map(function ($user) use ($currentUserId) {
                $roleAssignments = $this->countIfExists('user_roles', 'user_id', (int) $user->id);
                $approvalSubmissions = $this->countIfExists('approval_requests', 'submitted_by', (int) $user->id);
                $interviewEvaluators =
                    $this->countIfExists('college_admission_interview_evaluators', 'evaluator_user_id', (int) $user->id)
                    + $this->countIfExists('college_admission_interview_panel_evaluators', 'evaluator_user_id', (int) $user->id);

                $blocking = [];

                if ((int) $user->id === $currentUserId) {
                    $blocking[] = [
                        'table' => 'users',
                        'column' => 'current_authenticated_user',
                        'count' => 1,
                    ];
                }

                $superAdminAssignments = 0;
                if (Schema::hasTable('user_roles') && Schema::hasTable('roles')) {
                    $superAdminAssignments = DB::table('user_roles as ur')
                        ->join('roles as r', 'r.id', '=', 'ur.role_id')
                        ->where('ur.user_id', $user->id)
                        ->where('r.code', 'SUPER_ADMIN')
                        ->count();
                }

                if ($superAdminAssignments > 0) {
                    $blocking[] = [
                        'table' => 'user_roles',
                        'column' => 'SUPER_ADMIN',
                        'count' => $superAdminAssignments,
                    ];
                }

                if ($approvalSubmissions > 0) {
                    $blocking[] = [
                        'table' => 'approval_requests',
                        'column' => 'submitted_by',
                        'count' => $approvalSubmissions,
                    ];
                }

                if ($interviewEvaluators > 0) {
                    $blocking[] = [
                        'table' => 'college_admission_interview_evaluators',
                        'column' => 'evaluator_user_id',
                        'count' => $interviewEvaluators,
                    ];
                }

                $label = $user->name.' · '.(
                    $user->account_type === 'COLLEGE_STAFF'
                        ? 'College Staff'.($user->college_name ? ' · '.$user->college_name : '')
                        : 'University Staff'
                );

                return [
                    'id' => (int) $user->id,
                    'code' => (string) $user->email,
                    'name' => $label,
                    'status' => $user->status,
                    'kind' => $user->account_type,
                    'dependencies' => [
                        'role_assignments' => $roleAssignments,
                        'approval_submissions' => $approvalSubmissions,
                        'interview_evaluator_rows' => $interviewEvaluators,
                    ],
                    'blocked' => count($blocking) > 0,
                    'blocking_references' => $blocking,
                ];
            })
            ->values()
            ->all();
    }

    private function accessRoleRows(int $universityId): array
    {
        if (! Schema::hasTable('roles')) {
            return [];
        }

        $collegeReferences = Schema::hasTable('colleges')
            ? DB::table('colleges')
                ->where('university_id', $universityId)
                ->pluck('id')
                ->map(fn ($id) => 'college:'.$id)
            : collect();

        return DB::table('roles as r')
            ->where(function ($scope) use ($collegeReferences) {
                $scope
                    ->whereIn('r.owner_scope_type', ['GLOBAL', 'UNIVERSITY']);

                if ($collegeReferences->isNotEmpty()) {
                    $scope->orWhere(function ($collegeScope) use ($collegeReferences) {
                        $collegeScope
                            ->where('r.owner_scope_type', 'COLLEGE')
                            ->whereIn('r.owner_scope_reference', $collegeReferences);
                    });
                }
            })
            ->orderBy('r.name')
            ->orderBy('r.id')
            ->get([
                'r.id',
                'r.name',
                'r.code',
                'r.status',
                'r.is_system_role',
                'r.owner_scope_type',
                'r.owner_scope_reference',
            ])
            ->map(function ($role) {
                $assignedUsers = $this->countIfExists('user_roles', 'role_id', (int) $role->id);
                $permissions = $this->countIfExists('role_permissions', 'role_id', (int) $role->id);
                $workflowStages = $this->countIfExists('approval_workflow_stages', 'approver_role_id', (int) $role->id);
                $requestStages = $this->countIfExists('approval_request_stages', 'approver_role_id', (int) $role->id);

                $blocking = [];
                if ((bool) $role->is_system_role) {
                    $blocking[] = [
                        'table' => 'roles',
                        'column' => 'is_system_role',
                        'count' => 1,
                    ];
                }
                if ($workflowStages > 0) {
                    $blocking[] = [
                        'table' => 'approval_workflow_stages',
                        'column' => 'approver_role_id',
                        'count' => $workflowStages,
                    ];
                }
                if ($requestStages > 0) {
                    $blocking[] = [
                        'table' => 'approval_request_stages',
                        'column' => 'approver_role_id',
                        'count' => $requestStages,
                    ];
                }

                return [
                    'id' => (int) $role->id,
                    'code' => (string) $role->code,
                    'name' => (string) $role->name,
                    'status' => $role->status,
                    'kind' => ((bool) $role->is_system_role ? 'SYSTEM' : 'CUSTOM').' · '.$role->owner_scope_type,
                    'dependencies' => [
                        'assigned_users' => $assignedUsers,
                        'permissions' => $permissions,
                        'approval_workflow_stages' => $workflowStages,
                        'approval_request_stages' => $requestStages,
                    ],
                    'blocked' => count($blocking) > 0,
                    'blocking_references' => $blocking,
                ];
            })
            ->values()
            ->all();
    }

    private function cleanupAccessUser(
        int $id,
        int $universityId,
        int $actorId,
        bool $audit = true
    ): array {
        $this->assertCleanupEnabled();

        $row = collect($this->accessUserRows($universityId, $actorId))
            ->firstWhere('id', $id);

        if (! $row) {
            throw ValidationException::withMessages([
                'user' => 'The selected internal user is outside this University hierarchy or no longer exists.',
            ]);
        }

        if ($row['blocked']) {
            throw ValidationException::withMessages([
                'user' => 'This user is protected or has operational references and cannot be cleaned.',
            ]);
        }

        return DB::transaction(function () use ($id, $actorId, $row, $audit) {
            $email = DB::table('users')->where('id', $id)->value('email');

            if (Schema::hasTable('user_roles')) {
                DB::table('user_roles')->where('user_id', $id)->delete();
            }
            if (Schema::hasTable('passkeys') && Schema::hasColumn('passkeys', 'user_id')) {
                DB::table('passkeys')->where('user_id', $id)->delete();
            }
            if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                DB::table('sessions')->where('user_id', $id)->delete();
            }
            if ($email && Schema::hasTable('password_reset_tokens')) {
                DB::table('password_reset_tokens')->where('email', $email)->delete();
            }

            DB::table('users')->where('id', $id)->delete();

            if ($audit) {
                $this->audit(
                    'TEST_ACCESS_USER_CLEANED',
                    'User',
                    $id,
                    $row,
                    $actorId
                );
            }

            return $row;
        });
    }

    private function cleanupAccessRole(
        int $id,
        int $universityId,
        int $actorId,
        bool $audit = true
    ): array {
        $this->assertCleanupEnabled();

        $row = collect($this->accessRoleRows($universityId))
            ->firstWhere('id', $id);

        if (! $row) {
            throw ValidationException::withMessages([
                'role' => 'The selected role is outside this University hierarchy or no longer exists.',
            ]);
        }

        if ($row['blocked']) {
            throw ValidationException::withMessages([
                'role' => 'This role is protected or has operational references and cannot be cleaned.',
            ]);
        }

        return DB::transaction(function () use ($id, $actorId, $row, $audit) {
            if (Schema::hasTable('college_admission_form_access_roles')) {
                DB::table('college_admission_form_access_roles')->where('role_id', $id)->delete();
            }
            if (Schema::hasTable('user_roles')) {
                DB::table('user_roles')->where('role_id', $id)->delete();
            }
            if (Schema::hasTable('role_permissions')) {
                DB::table('role_permissions')->where('role_id', $id)->delete();
            }

            DB::table('roles')->where('id', $id)->delete();

            if ($audit) {
                $this->audit(
                    'TEST_ACCESS_ROLE_CLEANED',
                    'Role',
                    $id,
                    $row,
                    $actorId
                );
            }

            return $row;
        });
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
                    'status' => strtoupper(trim((string) $row->status)),
                    'status_normalized' => strtoupper(trim((string) $row->status)),
                    'kind' => 'ADMISSION_FORM_TEMPLATE',
                    // Lifecycle deactivation is independent of destructive-cleanup dependencies.
                    'can_deactivate_for_testing' => strtoupper(trim((string) $row->status)) === 'ACTIVE',
                    'dependencies' => [],
                    'blocked' => count($dependencies) > 0,
                    'blocking_references' => $dependencies,
                ];
            })->all();
    }

    /**
     * Remove admission-form rule rows that can reference fields through RESTRICT
     * foreign keys before the template -> step -> field cascade is allowed to run.
     *
     * Target-side rows also get deleted explicitly so cleanup remains deterministic
     * even when a rule points at another field in the same template tree.
     */
    private function deleteAdmissionFormFieldReferences($fieldIds): void
    {
        if ($fieldIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('college_admission_selection_rule_merit_sources')) {
            DB::table('college_admission_selection_rule_merit_sources')
                ->where(function ($query) use ($fieldIds) {
                    $query->whereIn('obtained_field_id', $fieldIds)
                        ->orWhereIn('maximum_field_id', $fieldIds);
                })
                ->delete();
        }

        if (Schema::hasTable('college_admission_form_field_copy_rules')) {
            DB::table('college_admission_form_field_copy_rules')
                ->where(function ($query) use ($fieldIds) {
                    $query->whereIn('target_field_id', $fieldIds)
                        ->orWhereIn('source_field_id', $fieldIds)
                        ->orWhereIn('trigger_field_id', $fieldIds);
                })
                ->delete();
        }

        if (Schema::hasTable('college_admission_form_field_comparisons')) {
            DB::table('college_admission_form_field_comparisons')
                ->where(function ($query) use ($fieldIds) {
                    $query->whereIn('target_field_id', $fieldIds)
                        ->orWhereIn('source_field_id', $fieldIds);
                })
                ->delete();
        }

        if (Schema::hasTable('college_admission_form_field_conditions')) {
            DB::table('college_admission_form_field_conditions')
                ->where(function ($query) use ($fieldIds) {
                    $query->whereIn('college_admission_form_field_id', $fieldIds)
                        ->orWhereIn('source_field_id', $fieldIds);
                })
                ->delete();
        }
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
        $this->deleteAdmissionFormFieldReferences($fieldIds);

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

    private function countCollegeAdmissionDocumentVerificationItemsForUniversity(int $universityId): int
    {
        if (! Schema::hasTable('college_admission_document_verification_items') || ! Schema::hasTable('college_admission_document_verifications')) {
            return 0;
        }

        return DB::table('college_admission_document_verification_items as i')
            ->join('college_admission_document_verifications as v', 'v.id', '=', 'i.college_admission_document_verification_id')
            ->join('colleges as c', 'c.id', '=', 'v.college_id')
            ->where('c.university_id', $universityId)
            ->count();
    }

    private function collegeAdmissionDocumentVerificationRows(int $universityId): array
    {
        if (! Schema::hasTable('college_admission_document_verifications')) {
            return [];
        }

        return DB::table('college_admission_document_verifications as v')
            ->join('colleges as c', 'c.id', '=', 'v.college_id')
            ->join('college_admission_applications as a', 'a.id', '=', 'v.college_admission_application_id')
            ->where('c.university_id', $universityId)
            ->orderByDesc('v.id')
            ->get(['v.id', 'v.status', 'a.application_no', 'a.candidate_name', 'c.name as college_name'])
            ->map(function ($row) {
                $downstream = $this->downstreamReferences(
                    $row->id,
                    [['college_admission_seat_allocations', 'college_admission_document_verification_id']]
                );

                return [
                    'id' => (int) $row->id,
                    'code' => $row->application_no,
                    'name' => $row->candidate_name.' · '.$row->college_name,
                    'status' => $row->status,
                    'kind' => 'DOCUMENT VERIFICATION',
                    'dependencies' => [
                        'review_items' => $this->countIfExists(
                            'college_admission_document_verification_items',
                            'college_admission_document_verification_id',
                            (int) $row->id
                        ),
                    ],
                    'blocked' => count($downstream) > 0,
                    'blocking_references' => $downstream,
                ];
            })
            ->values()
            ->all();
    }

    private function cleanupCollegeAdmissionDocumentVerification(int $id, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('college_admission_document_verifications')) {
            abort(404);
        }

        $record = DB::table('college_admission_document_verifications as v')
            ->join('colleges as c', 'c.id', '=', 'v.college_id')
            ->join('college_admission_applications as a', 'a.id', '=', 'v.college_admission_application_id')
            ->where('v.id', $id)
            ->where('c.university_id', $universityId)
            ->select('v.*', 'a.application_no', 'a.candidate_name', 'c.name as college_name')
            ->first();

        if (! $record) {
            abort(404);
        }

        $downstream = $this->downstreamReferences(
            $id,
            [['college_admission_seat_allocations', 'college_admission_document_verification_id']]
        );
        if (count($downstream) > 0) {
            throw ValidationException::withMessages([
                'record' => 'This Document Verification is already consumed by Seat Allocation. Clean the dependent Seat Allocation test record first.',
            ]);
        }

        return DB::transaction(function () use ($record, $actorId) {
            $itemCount = $this->countIfExists(
                'college_admission_document_verification_items',
                'college_admission_document_verification_id',
                (int) $record->id
            );
            $this->deleteWhereIn(
                'college_admission_document_verification_items',
                'college_admission_document_verification_id',
                collect([(int) $record->id])
            );
            DB::table('college_admission_document_verifications')->where('id', $record->id)->delete();

            $result = ['record' => (array) $record, 'deleted_review_items' => $itemCount];
            $this->audit(
                'TEST_COLLEGE_ADMISSION_DOCUMENT_VERIFICATION_CLEANED',
                'test_data_cleanup',
                (int) $record->id,
                $result,
                $actorId
            );

            return $result;
        });
    }

    private function collegeAdmissionSeatAllocationRows(int $universityId): array
    {
        if (! Schema::hasTable('college_admission_seat_allocations')) {
            return [];
        }

        return DB::table('college_admission_seat_allocations as sa')
            ->join('colleges as c', 'c.id', '=', 'sa.college_id')
            ->join('college_admission_applications as a', 'a.id', '=', 'sa.college_admission_application_id')
            ->where('c.university_id', $universityId)
            ->orderByDesc('sa.id')
            ->get([
                'sa.id', 'sa.status', 'sa.physical_seat_type', 'sa.physical_category_code',
                'sa.merit_rank', 'a.application_no', 'a.candidate_name', 'a.admission_mode', 'c.name as college_name',
            ])
            ->map(function ($row) {
                $downstream = $this->downstreamReferences(
                    $row->id,
                    [['admissions', 'college_admission_seat_allocation_id']]
                );

                return [
                    'id' => (int) $row->id,
                    'code' => $row->application_no.' · '.(strtoupper((string) ($row->admission_mode ?? 'REGULAR')) === 'DIRECT' ? 'Direct Admission' : 'Rank #'.$row->merit_rank),
                    'name' => $row->candidate_name.' · '.$row->college_name,
                    'status' => $row->status,
                    'kind' => $row->physical_seat_type.($row->physical_category_code ? ' · '.$row->physical_category_code : ''),
                    'dependencies' => [
                        'horizontal_categories' => $this->countIfExists(
                            'college_admission_seat_allocation_horizontal_categories',
                            'college_admission_seat_allocation_id',
                            (int) $row->id
                        ),
                    ],
                    'blocked' => count($downstream) > 0,
                    'blocking_references' => $downstream,
                ];
            })
            ->values()
            ->all();
    }

    private function cleanupCollegeAdmissionSeatAllocation(
        int $id,
        int $universityId,
        int $actorId
    ): array {
        if (! Schema::hasTable('college_admission_seat_allocations')) {
            abort(404);
        }

        $record = DB::table('college_admission_seat_allocations as sa')
            ->join('colleges as c', 'c.id', '=', 'sa.college_id')
            ->join('college_admission_applications as a', 'a.id', '=', 'sa.college_admission_application_id')
            ->where('sa.id', $id)
            ->where('c.university_id', $universityId)
            ->select('sa.*', 'a.application_no', 'a.candidate_name', 'c.name as college_name')
            ->first();

        if (! $record) {
            abort(404);
        }

        $downstream = $this->downstreamReferences(
            $id,
            [['admissions', 'college_admission_seat_allocation_id']]
        );
        if (count($downstream) > 0) {
            throw ValidationException::withMessages([
                'record' => 'This Seat Allocation is already consumed by Admission Confirmation. Clean the dependent Admission test record first.',
            ]);
        }

        return DB::transaction(function () use ($record, $actorId) {
            $horizontalCount = $this->countIfExists(
                'college_admission_seat_allocation_horizontal_categories',
                'college_admission_seat_allocation_id',
                (int) $record->id
            );
            $this->deleteWhereIn(
                'college_admission_seat_allocation_horizontal_categories',
                'college_admission_seat_allocation_id',
                collect([(int) $record->id])
            );
            DB::table('college_admission_seat_allocations')->where('id', $record->id)->delete();

            $result = [
                'record' => (array) $record,
                'deleted_horizontal_categories' => $horizontalCount,
            ];
            $this->audit(
                'TEST_COLLEGE_ADMISSION_SEAT_ALLOCATION_CLEANED',
                'test_data_cleanup',
                (int) $record->id,
                $result,
                $actorId
            );

            return $result;
        });
    }


    private function admissionRows(int $universityId): array
    {
        if (! Schema::hasTable('admissions')) {
            return [];
        }

        return DB::table('admissions as ad')
            ->join('colleges as c', 'c.id', '=', 'ad.college_id')
            ->join('college_admission_applications as a', 'a.id', '=', 'ad.college_admission_application_id')
            ->where('c.university_id', $universityId)
            ->orderByDesc('ad.id')
            ->get([
                'ad.id', 'ad.admission_no', 'ad.status', 'ad.college_admission_application_id',
                'ad.college_admission_seat_allocation_id', 'a.application_no', 'a.candidate_name',
                'c.name as college_name',
            ])
            ->map(function ($row) {
                $downstream = [];
                if (Schema::hasTable('students')) {
                    if (Schema::hasColumn('students', 'admission_id')) {
                        $downstream = array_merge($downstream, $this->downstreamReferences($row->id, [['students', 'admission_id']]));
                    }
                    if (Schema::hasColumn('students', 'college_admission_application_id')) {
                        $downstream = array_merge($downstream, $this->downstreamReferences($row->college_admission_application_id, [['students', 'college_admission_application_id']]));
                    }
                }

                return [
                    'id' => (int) $row->id,
                    'code' => $row->admission_no,
                    'name' => $row->candidate_name.' · '.$row->application_no.' · '.$row->college_name,
                    'status' => $row->status,
                    'kind' => 'ADMISSION_CONFIRMATION',
                    'dependencies' => [],
                    'blocked' => count($downstream) > 0,
                    'blocking_references' => $downstream,
                ];
            })
            ->values()
            ->all();
    }

    private function cleanupAdmission(int $id, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('admissions')) {
            abort(404);
        }

        $record = DB::table('admissions as ad')
            ->join('colleges as c', 'c.id', '=', 'ad.college_id')
            ->join('college_admission_applications as a', 'a.id', '=', 'ad.college_admission_application_id')
            ->where('ad.id', $id)
            ->where('c.university_id', $universityId)
            ->select('ad.*', 'a.application_no', 'a.candidate_name', 'c.name as college_name')
            ->first();

        if (! $record) {
            abort(404);
        }

        $downstream = $this->downstreamReferences($record->id, [
            ['fee_demands', 'admission_id'],
        ]);
        if (Schema::hasTable('students')) {
            if (Schema::hasColumn('students', 'admission_id')) {
                $downstream = array_merge($downstream, $this->downstreamReferences($record->id, [['students', 'admission_id']]));
            }
            if (Schema::hasColumn('students', 'college_admission_application_id')) {
                $downstream = array_merge($downstream, $this->downstreamReferences($record->college_admission_application_id, [['students', 'college_admission_application_id']]));
            }
        }

        if (count($downstream) > 0) {
            throw ValidationException::withMessages([
                'record' => 'This Admission Confirmation is already consumed by Fee Demand or Student Enrollment. Clean the dependent Fee Demand/Student test record first.',
            ]);
        }

        DB::table('admissions')->where('id', $record->id)->delete();
        $result = ['record' => (array) $record];
        $this->audit('TEST_ADMISSION_CONFIRMATION_CLEANED', 'test_data_cleanup', (int) $record->id, $result, $actorId);

        return $result;
    }


    private function collegeAdmissionMeritRosterRows(int $universityId): array
    {
        if (! Schema::hasTable('college_admission_merit_entries')) {
            return [];
        }

        return DB::table('college_admission_merit_entries as me')
            ->join('college_admission_selection_rules as sr', 'sr.id', '=', 'me.college_admission_selection_rule_id')
            ->join('college_program_intakes as i', 'i.id', '=', 'sr.college_program_intake_id')
            ->join('college_program_offerings as po', 'po.id', '=', 'i.college_program_offering_id')
            ->join('program_templates as pt', 'pt.id', '=', 'po.program_template_id')
            ->join('colleges as c', 'c.id', '=', 'po.college_id')
            ->where('c.university_id', $universityId)
            ->groupBy(
                'sr.id',
                'sr.code',
                'sr.name',
                'sr.version_no',
                'sr.bucket_key',
                'pt.name',
                'pt.code',
                'c.name',
                'me.generation_batch',
                'me.generated_at'
            )
            ->orderByDesc(DB::raw('MAX(me.generated_at)'))
            ->get([
                'sr.id',
                'sr.code',
                'sr.name',
                'sr.version_no',
                'sr.bucket_key',
                'pt.name as program_name',
                'pt.code as program_code',
                'c.name as college_name',
                'me.generation_batch',
                'me.generated_at',
                DB::raw('COUNT(me.id) as ranked_entries'),
            ])
            ->map(function ($row) {
                $entryIds = DB::table('college_admission_merit_entries')
                    ->where('college_admission_selection_rule_id', $row->id)
                    ->pluck('id');

                $seatAllocations = $this->countWhereIn(
                    'college_admission_seat_allocations',
                    'college_admission_merit_entry_id',
                    $entryIds
                );

                return [
                    'id' => (int) $row->id,
                    'code' => $row->code.' · V'.$row->version_no,
                    'name' => $row->program_name.' Merit / Roster · '.$row->college_name,
                    'status' => 'GENERATED',
                    'kind' => $row->bucket_key,
                    'dependencies' => [
                        'ranked_entries' => (int) $row->ranked_entries,
                        'seat_allocations' => $seatAllocations,
                    ],
                    'blocked' => $seatAllocations > 0,
                    'blocking_references' => $seatAllocations > 0
                        ? [['table' => 'college_admission_seat_allocations', 'count' => $seatAllocations]]
                        : [],
                    'generation_batch' => $row->generation_batch,
                    'generated_at' => $row->generated_at,
                ];
            })
            ->values()
            ->all();
    }

    private function cleanupCollegeAdmissionMeritRoster(
        int $selectionRuleId,
        int $universityId,
        int $actorId
    ): array {
        if (! Schema::hasTable('college_admission_merit_entries')) {
            abort(404);
        }

        $rule = DB::table('college_admission_selection_rules as sr')
            ->join('college_program_intakes as i', 'i.id', '=', 'sr.college_program_intake_id')
            ->join('college_program_offerings as po', 'po.id', '=', 'i.college_program_offering_id')
            ->join('program_templates as pt', 'pt.id', '=', 'po.program_template_id')
            ->join('colleges as c', 'c.id', '=', 'po.college_id')
            ->where('sr.id', $selectionRuleId)
            ->where('c.university_id', $universityId)
            ->select([
                'sr.id', 'sr.code', 'sr.name', 'sr.version_no', 'sr.bucket_key',
                'pt.name as program_name', 'pt.code as program_code', 'c.name as college_name',
            ])
            ->first();

        if (! $rule) {
            abort(404);
        }

        $entryIds = DB::table('college_admission_merit_entries')
            ->where('college_admission_selection_rule_id', $selectionRuleId)
            ->pluck('id');

        if ($entryIds->isEmpty()) {
            abort(404);
        }

        $seatAllocations = $this->countWhereIn(
            'college_admission_seat_allocations',
            'college_admission_merit_entry_id',
            $entryIds
        );

        if ($seatAllocations > 0) {
            throw ValidationException::withMessages([
                'record' => 'This generated Merit / Roster is already consumed by Seat Allocation. Clean the dependent Seat Allocation test records first.',
            ]);
        }

        return DB::transaction(function () use ($rule, $entryIds, $selectionRuleId, $actorId) {
            $entries = DB::table('college_admission_merit_entries')
                ->whereIn('id', $entryIds)
                ->orderBy('rank')
                ->get()
                ->map(fn ($row) => (array) $row)
                ->all();

            $deleted = DB::table('college_admission_merit_entries')
                ->whereIn('id', $entryIds)
                ->delete();

            $result = [
                'selection_rule' => (array) $rule,
                'deleted_merit_entries' => $deleted,
                'preserved' => [
                    'Selection Rule version',
                    'Admission Applications and Choices',
                    'Normalized Scores',
                ],
            ];

            $this->audit(
                'TEST_COLLEGE_ADMISSION_MERIT_ROSTER_CLEANED',
                'test_data_cleanup',
                $selectionRuleId,
                [
                    ...$result,
                    'deleted_entries' => $entries,
                ],
                $actorId
            );

            return $result;
        });
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

        // Only independently processed downstream records block direct application cleanup.
        // Application-owned form data, interview/evaluation rows, normalized scores and
        // document-verification rows are removed automatically once no Merit / Seat /
        // Admission / Student record consumes the application.
        $downstream = $this->downstreamReferences(
            $id,
            [
                ['college_admission_merit_entries', 'college_admission_application_id'],
                ['college_admission_seat_allocations', 'college_admission_application_id'],
                ['admissions', 'college_admission_application_id'],
                ['students', 'college_admission_application_id'],
            ]
        );

        if (count($downstream) > 0) {
            throw ValidationException::withMessages([
                'record' => 'This Admission Application is already consumed by Merit / Seat / Admission / Student records. Clean those dependent test records first.',
            ]);
        }

        return DB::transaction(function () use ($record, $actorId) {
            $choiceCount = $this->countIfExists(
                'college_admission_application_choices',
                'college_admission_application_id',
                $record->id
            );
            $fieldValueCount = $this->countIfExists(
                'college_admission_application_field_values',
                'college_admission_application_id',
                $record->id
            );
            $academicPreferenceCount = $this->countIfExists(
                'college_admission_application_academic_preferences',
                'college_admission_application_id',
                $record->id
            );
            $courseChoiceCount = $this->countIfExists(
                'college_admission_application_course_choices',
                'college_admission_application_id',
                $record->id
            );

            $applicationIds = collect([$record->id]);

            // Capture private upload paths before removing field-value rows so the
            // physical files do not become orphaned in storage.
            $uploadPaths = collect();
            if (Schema::hasTable('college_admission_application_field_values')
                && Schema::hasColumn('college_admission_application_field_values', 'file_path')) {
                $uploadPaths = DB::table('college_admission_application_field_values')
                    ->where('college_admission_application_id', $record->id)
                    ->whereNotNull('file_path')
                    ->pluck('file_path')
                    ->filter();
            }

            // Application-owned processing rows are safe to remove together once
            // Merit / Seat / Admission / Student references have already been cleaned.
            $interviewIds = Schema::hasTable('college_admission_interviews')
                ? DB::table('college_admission_interviews')
                    ->where('college_admission_application_id', $record->id)
                    ->pluck('id')
                : collect();
            $interviewCount = $interviewIds->count();
            $scoreCount = $this->countIfExists(
                'college_admission_scores',
                'college_admission_application_id',
                $record->id
            );
            $verificationIds = Schema::hasTable('college_admission_document_verifications')
                ? DB::table('college_admission_document_verifications')
                    ->where('college_admission_application_id', $record->id)
                    ->pluck('id')
                : collect();
            $verificationCount = $verificationIds->count();

            $this->deleteWhereIn('college_admission_interview_evaluators', 'college_admission_interview_id', $interviewIds);
            $this->deleteWhereIn('college_admission_interviews', 'id', $interviewIds);
            $this->deleteWhereIn('college_admission_scores', 'college_admission_application_id', $applicationIds);
            $this->deleteWhereIn('college_admission_document_verification_items', 'college_admission_document_verification_id', $verificationIds);
            $this->deleteWhereIn('college_admission_document_verifications', 'id', $verificationIds);

            $this->deleteWhereIn('college_admission_application_field_values', 'college_admission_application_id', $applicationIds);
            $this->deleteWhereIn('college_admission_application_course_choices', 'college_admission_application_id', $applicationIds);
            $this->deleteWhereIn('college_admission_application_academic_preferences', 'college_admission_application_id', $applicationIds);
            $this->deleteWhereIn('college_admission_application_choices', 'college_admission_application_id', $applicationIds);
            DB::table('college_admission_applications')->where('id', $record->id)->delete();

            foreach ($uploadPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            $result = [
                'record' => (array) $record,
                'deleted_choices' => $choiceCount,
                'deleted_dynamic_field_values' => $fieldValueCount,
                'deleted_academic_preferences' => $academicPreferenceCount,
                'deleted_course_choices' => $courseChoiceCount,
                'deleted_interviews' => $interviewCount,
                'deleted_scores' => $scoreCount,
                'deleted_document_verifications' => $verificationCount,
                'deleted_private_files' => $uploadPaths->count(),
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

    private function applicantRows(int $universityId): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('applicant_profiles') || ! Schema::hasTable('colleges')) {
            return [];
        }

        return DB::table('users as u')
            ->join('applicant_profiles as ap', 'ap.user_id', '=', 'u.id')
            ->join('colleges as c', 'c.id', '=', 'ap.college_id')
            ->where('c.university_id', $universityId)
            ->where('u.account_type', 'APPLICANT')
            ->orderBy('c.name')
            ->orderBy('u.name')
            ->get([
                'u.id', 'u.name', 'u.email', 'u.status',
                'ap.id as applicant_profile_id', 'ap.registration_no',
                'ap.lifecycle_status', 'ap.student_id',
                'c.id as college_id', 'c.name as college_name', 'c.code as college_code',
            ])
            ->map(function ($row) {
                $applications = Schema::hasTable('college_admission_applications')
                    ? DB::table('college_admission_applications as a')
                        ->leftJoin('college_admission_cycles as ac', 'ac.id', '=', 'a.college_admission_cycle_id')
                        ->leftJoin('college_program_offerings as po', 'po.id', '=', 'ac.college_program_offering_id')
                        ->leftJoin('program_templates as pt', 'pt.id', '=', 'po.program_template_id')
                        ->where('a.applicant_user_id', $row->id)
                        ->orderByDesc('a.id')
                        ->get([
                            'a.id', 'a.application_no', 'a.status as application_status',
                            'po.id as program_offering_id',
                            'pt.name as program_name', 'pt.code as program_code',
                        ])
                    : collect();

                $applicationIds = $applications->pluck('id');
                $offeringRows = $applications
                    ->filter(fn ($app) => $app->program_offering_id !== null)
                    ->unique('program_offering_id')
                    ->values();

                $blocking = [];
                if ($row->lifecycle_status === 'STUDENT_ENABLED' || $row->student_id !== null) {
                    $blocking[] = ['table' => 'applicant_profiles', 'column' => 'student_id', 'count' => 1];
                }

                foreach ([['admissions', 'college_admission_application_id'], ['students', 'college_admission_application_id']] as [$table, $column]) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, $column) && $applicationIds->isNotEmpty()) {
                        $count = DB::table($table)->whereIn($column, $applicationIds)->count();
                        if ($count > 0) {
                            $blocking[] = ['table' => $table, 'column' => $column, 'count' => $count];
                        }
                    }
                }

                return [
                    'id' => (int) $row->id,
                    'code' => $row->registration_no ?: 'USER-'.$row->id,
                    'name' => $row->name.' · '.$row->college_name,
                    'status' => $row->status,
                    'kind' => 'APPLICANT',
                    'dependencies' => [
                        'applications' => $applicationIds->count(),
                        'program_offerings' => $offeringRows->count(),
                        'seat_allocations' => $this->countWhereIn('college_admission_seat_allocations', 'college_admission_application_id', $applicationIds),
                        'merit_entries' => $this->countWhereIn('college_admission_merit_entries', 'college_admission_application_id', $applicationIds),
                        'document_verifications' => $this->countWhereIn('college_admission_document_verifications', 'college_admission_application_id', $applicationIds),
                    ],
                    'blocked' => count($blocking) > 0,
                    'blocking_references' => $blocking,
                    'email' => $row->email,
                    'college_name' => $row->college_name,
                    'program_offering_ids' => $offeringRows->pluck('program_offering_id')->map(fn ($id) => (int) $id)->all(),
                    'program_offerings' => $offeringRows->map(fn ($offering) => [
                        'id' => (int) $offering->program_offering_id,
                        'code' => $offering->program_code,
                        'name' => $offering->program_name ?: $offering->program_code ?: 'Program Offering #'.$offering->program_offering_id,
                    ])->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function cleanupApplicant(int $userId, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('applicant_profiles')) {
            abort(404);
        }

        $record = DB::table('users as u')
            ->join('applicant_profiles as ap', 'ap.user_id', '=', 'u.id')
            ->join('colleges as c', 'c.id', '=', 'ap.college_id')
            ->where('u.id', $userId)
            ->where('u.account_type', 'APPLICANT')
            ->where('c.university_id', $universityId)
            ->select([
                'u.id', 'u.name', 'u.email',
                'ap.id as applicant_profile_id', 'ap.registration_no',
                'ap.lifecycle_status', 'ap.student_id',
                'c.id as college_id', 'c.name as college_name',
            ])
            ->first();

        if (! $record) {
            abort(404);
        }

        if ($record->lifecycle_status === 'STUDENT_ENABLED' || $record->student_id !== null) {
            throw ValidationException::withMessages([
                'record' => 'This applicant has already been promoted/linked to Student data. Applicant identity cleanup is blocked.',
            ]);
        }

        $applicationIds = Schema::hasTable('college_admission_applications')
            ? DB::table('college_admission_applications')->where('applicant_user_id', $record->id)->pluck('id')
            : collect();

        foreach ([['admissions', 'college_admission_application_id'], ['students', 'college_admission_application_id']] as [$table, $column]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column) && $applicationIds->isNotEmpty() && DB::table($table)->whereIn($column, $applicationIds)->exists()) {
                throw ValidationException::withMessages([
                    'record' => 'This applicant already has Admission/Student downstream data. Cleanup is blocked to protect the student lifecycle.',
                ]);
            }
        }

        return DB::transaction(function () use ($record, $applicationIds, $actorId) {
            $counts = [
                'applications' => $applicationIds->count(),
                'seat_allocations' => 0,
                'merit_entries' => 0,
                'document_verifications' => 0,
                'scores' => 0,
                'interviews' => 0,
            ];

            if ($applicationIds->isNotEmpty()) {
                $seatAllocationIds = Schema::hasTable('college_admission_seat_allocations')
                    ? DB::table('college_admission_seat_allocations')->whereIn('college_admission_application_id', $applicationIds)->pluck('id')
                    : collect();
                $verificationIds = Schema::hasTable('college_admission_document_verifications')
                    ? DB::table('college_admission_document_verifications')->whereIn('college_admission_application_id', $applicationIds)->pluck('id')
                    : collect();

                $counts['seat_allocations'] = $seatAllocationIds->count();
                $counts['document_verifications'] = $verificationIds->count();
                $counts['merit_entries'] = $this->countWhereIn('college_admission_merit_entries', 'college_admission_application_id', $applicationIds);
                $counts['scores'] = $this->countWhereIn('college_admission_scores', 'college_admission_application_id', $applicationIds);
                $counts['interviews'] = $this->countWhereIn('college_admission_interviews', 'college_admission_application_id', $applicationIds);

                $this->deleteWhereIn('college_admission_seat_allocation_horizontal_categories', 'college_admission_seat_allocation_id', $seatAllocationIds);
                $this->deleteWhereIn('college_admission_seat_allocations', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_document_verification_items', 'college_admission_document_verification_id', $verificationIds);
                $this->deleteWhereIn('college_admission_document_verifications', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_merit_entries', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_scores', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_interviews', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_application_field_values', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_application_course_choices', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_application_academic_preferences', 'college_admission_application_id', $applicationIds);
                $this->deleteWhereIn('college_admission_application_choices', 'college_admission_application_id', $applicationIds);
                DB::table('college_admission_applications')->whereIn('id', $applicationIds)->delete();
            }

            DB::table('applicant_profiles')->where('id', $record->applicant_profile_id)->delete();

            if (Schema::hasTable('user_roles') && Schema::hasColumn('user_roles', 'user_id')) {
                DB::table('user_roles')->where('user_id', $record->id)->delete();
            }

            DB::table('users')->where('id', $record->id)->delete();

            $result = ['applicant' => (array) $record, 'deleted' => $counts];

            $this->audit('TEST_APPLICANT_CLEANED', 'test_data_cleanup', $record->id, $result, $actorId);

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
                'a.college_id', 'a.applicant_user_id', 'a.college_admission_cycle_id',
                'a.admission_mode',
                ...(Schema::hasColumn('college_admission_applications', 'entry_source') ? ['a.entry_source'] : []),
                'c.name as college_name', 'ac.name as cycle_name',
            ])
            ->map(function ($row) {
                $downstream = $this->downstreamReferences(
                    $row->id,
                    [
                        ['college_admission_merit_entries', 'college_admission_application_id'],
                        ['college_admission_seat_allocations', 'college_admission_application_id'],
                        ['admissions', 'college_admission_application_id'],
                        ['students', 'college_admission_application_id'],
                    ]
                );

                $programChoiceCount = $this->countIfExists(
                    'college_admission_application_choices',
                    'college_admission_application_id',
                    $row->id
                );
                $isLegacyUnlinked = $row->status === 'SUBMITTED'
                    && $row->admission_mode === 'REGULAR'
                    && (! property_exists($row, 'entry_source') || $row->entry_source === 'PUBLIC')
                    && $programChoiceCount === 0;

                $duplicateCount = $row->applicant_user_id
                    ? DB::table('college_admission_applications')
                        ->where('college_id', $row->college_id)
                        ->where('college_admission_cycle_id', $row->college_admission_cycle_id)
                        ->where('applicant_user_id', $row->applicant_user_id)
                        ->whereIn('status', ['DRAFT', 'SUBMITTED'])
                        ->count()
                    : 0;

                return [
                    'id' => $row->id,
                    'code' => $row->application_no,
                    'name' => $row->candidate_name.' · '.$row->college_name.' · '.($row->cycle_name ?? 'Admission Cycle'),
                    'status' => $row->status,
                    'kind' => $duplicateCount > 1
                        ? 'DUPLICATE APPLICATION'
                        : ($isLegacyUnlinked ? 'LEGACY UNLINKED REGULAR APPLICATION' : 'APPLICATION'),
                    'dependencies' => [
                        'program_choices' => $programChoiceCount,
                        'dynamic_field_values' => $this->countIfExists(
                            'college_admission_application_field_values',
                            'college_admission_application_id',
                            $row->id
                        ),
                        'academic_preferences' => $this->countIfExists(
                            'college_admission_application_academic_preferences',
                            'college_admission_application_id',
                            $row->id
                        ),
                        'curriculum_course_choices' => $this->countIfExists(
                            'college_admission_application_course_choices',
                            'college_admission_application_id',
                            $row->id
                        ),
                        'interviews' => $this->countIfExists(
                            'college_admission_interviews',
                            'college_admission_application_id',
                            $row->id
                        ),
                        'scores' => $this->countIfExists(
                            'college_admission_scores',
                            'college_admission_application_id',
                            $row->id
                        ),
                        'document_verifications' => $this->countIfExists(
                            'college_admission_document_verifications',
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
            $meritSourceCount = $this->countIfExists(
                'college_admission_selection_rule_merit_sources',
                'college_admission_selection_rule_id',
                $record->id
            );

            $this->deleteWhereIn('college_admission_selection_rule_merit_sources', 'college_admission_selection_rule_id', collect([$record->id]));
            $this->deleteWhereIn(
                'college_admission_selection_rule_tiebreakers',
                'college_admission_selection_rule_id',
                collect([$record->id])
            );
            DB::table('college_admission_selection_rules')->where('id', $record->id)->delete();

            $result = [
                'record' => (array) $record,
                'deleted_tie_breakers' => $tieBreakerCount,
                'deleted_merit_sources' => $meritSourceCount,
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

    private function cleanupSection(
        int $id,
        int $universityId,
        int $actorId
    ): array {
        if (! Schema::hasTable('sections')) {
            abort(404);
        }

        $record = DB::table('sections as s')
            ->join('batches as b', 'b.id', '=', 's.batch_id')
            ->join('college_program_offerings as cpo', 'cpo.id', '=', 'b.college_program_offering_id')
            ->join('colleges as c', 'c.id', '=', 'cpo.college_id')
            ->where('s.id', $id)
            ->where('c.university_id', $universityId)
            ->select('s.*')
            ->first();

        if (! $record) {
            abort(404);
        }

        $downstream = $this->downstreamReferences($id, [
            ['student_enrollments', 'section_id'],
        ]);

        if (count($downstream) > 0) {
            throw ValidationException::withMessages([
                'record' => 'This Section already has Student Enrollment records. Clean those dependent test records first.',
            ]);
        }

        DB::table('sections')->where('id', $id)->delete();
        $this->audit('TEST_COLLEGE_SECTION_CLEANED', 'test_data_cleanup', $id, ['record' => (array) $record], $actorId);

        return ['record' => (array) $record];
    }

    private function sectionRows(int $universityId): array
    {
        if (! Schema::hasTable('sections')) {
            return [];
        }

        return DB::table('sections as sec')
            ->join('batches as b', 'b.id', '=', 'sec.batch_id')
            ->join('college_program_offerings as cpo', 'cpo.id', '=', 'b.college_program_offering_id')
            ->join('colleges as c', 'c.id', '=', 'cpo.college_id')
            ->join('program_templates as pt', 'pt.id', '=', 'cpo.program_template_id')
            ->join('academic_sessions as s', 's.id', '=', 'cpo.academic_session_id')
            ->where('c.university_id', $universityId)
            ->orderBy('c.name')->orderBy('pt.name')->orderBy('b.name')->orderBy('sec.name')
            ->get([
                'sec.id', 'sec.code', 'sec.name', 'sec.status', 'b.name as batch_name',
                'c.name as college_name', 'pt.name as program_name', 's.name as session_name',
            ])
            ->map(function ($row) {
                $downstream = $this->downstreamReferences($row->id, [
                    ['student_enrollments', 'section_id'],
                ]);

                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => $row->college_name.' · '.$row->program_name.' · '.$row->batch_name.' · '.$row->name.' · '.$row->session_name,
                    'status' => $row->status,
                    'kind' => 'COLLEGE_SECTION',
                    'dependencies' => [],
                    'blocked' => count($downstream) > 0,
                    'blocking_references' => $downstream,
                ];
            })
            ->values()->all();
    }

    private function countSectionsForUniversity(int $universityId): int
    {
        if (! Schema::hasTable('sections')) {
            return 0;
        }

        return (int) DB::table('sections as sec')
            ->join('batches as b', 'b.id', '=', 'sec.batch_id')
            ->join('college_program_offerings as cpo', 'cpo.id', '=', 'b.college_program_offering_id')
            ->join('colleges as c', 'c.id', '=', 'cpo.college_id')
            ->where('c.university_id', $universityId)
            ->count();
    }

    private function cleanupBatch(
        int $id,
        int $universityId,
        int $actorId
    ): array {
        if (! Schema::hasTable('batches')) {
            abort(404);
        }

        $record = DB::table('batches as b')
            ->join('college_program_offerings as cpo', 'cpo.id', '=', 'b.college_program_offering_id')
            ->join('colleges as c', 'c.id', '=', 'cpo.college_id')
            ->where('b.id', $id)
            ->where('c.university_id', $universityId)
            ->select('b.*')
            ->first();

        if (! $record) {
            abort(404);
        }

        $downstream = $this->downstreamReferences(
            $id,
            [
                ['sections', 'batch_id'],
                ['student_enrollments', 'batch_id'],
            ]
        );

        if (count($downstream) > 0) {
            throw ValidationException::withMessages([
                'record' => 'This Batch already has downstream Section/Student records. Clean those dependent test records first.',
            ]);
        }

        DB::table('batches')->where('id', $id)->delete();

        $this->audit(
            'TEST_COLLEGE_BATCH_CLEANED',
            'test_data_cleanup',
            $id,
            ['record' => (array) $record],
            $actorId
        );

        return ['record' => (array) $record];
    }

    private function batchRows(int $universityId): array
    {
        if (! Schema::hasTable('batches')) {
            return [];
        }

        return DB::table('batches as b')
            ->join('college_program_offerings as cpo', 'cpo.id', '=', 'b.college_program_offering_id')
            ->join('colleges as c', 'c.id', '=', 'cpo.college_id')
            ->join('program_templates as pt', 'pt.id', '=', 'cpo.program_template_id')
            ->join('academic_sessions as s', 's.id', '=', 'cpo.academic_session_id')
            ->where('c.university_id', $universityId)
            ->orderBy('c.name')
            ->orderBy('pt.name')
            ->orderBy('b.name')
            ->get([
                'b.id', 'b.code', 'b.name', 'b.status',
                'c.name as college_name', 'pt.name as program_name', 's.name as session_name',
            ])
            ->map(function ($row) {
                $downstream = $this->downstreamReferences(
                    $row->id,
                    [
                        ['sections', 'batch_id'],
                        ['student_enrollments', 'batch_id'],
                    ]
                );

                return [
                    'id' => $row->id,
                    'code' => $row->code,
                    'name' => $row->college_name.' · '.$row->program_name.' · '.$row->name.' · '.$row->session_name,
                    'status' => $row->status,
                    'kind' => 'COLLEGE_BATCH',
                    'dependencies' => [],
                    'blocked' => count($downstream) > 0,
                    'blocking_references' => $downstream,
                ];
            })
            ->values()
            ->all();
    }

    private function countBatchesForUniversity(int $universityId): int
    {
        if (! Schema::hasTable('batches')) {
            return 0;
        }

        return (int) DB::table('batches as b')
            ->join('college_program_offerings as cpo', 'cpo.id', '=', 'b.college_program_offering_id')
            ->join('colleges as c', 'c.id', '=', 'cpo.college_id')
            ->where('c.university_id', $universityId)
            ->count();
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
                ['fee_late_fine_rules', 'college_program_offering_id'],
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

    private function cleanupCollegeAcademicCalendar(int $id, int $universityId, int $actorId): array
    {
        if (! Schema::hasTable('college_academic_calendars')) abort(404);
        $record = DB::table('college_academic_calendars as cac')
            ->join('colleges as c', 'c.id', '=', 'cac.college_id')
            ->where('cac.id', $id)->where('c.university_id', $universityId)
            ->select('cac.*')->first();
        if (! $record) abort(404);

        return DB::transaction(function () use ($record, $actorId) {
            $overrideCount = $this->countIfExists('college_calendar_overrides', 'college_academic_calendar_id', $record->id);
            if (Schema::hasTable('college_calendar_overrides')) {
                DB::table('college_calendar_overrides')->where('college_academic_calendar_id', $record->id)->delete();
            }
            DB::table('college_academic_calendars')->where('id', $record->id)->delete();
            $result = ['record' => (array) $record, 'deleted_overrides' => $overrideCount];
            $this->audit('TEST_COLLEGE_ACADEMIC_CALENDAR_CLEANED', 'test_data_cleanup', $record->id, $result, $actorId);
            return $result;
        });
    }

    private function collegeAcademicCalendarRows(int $universityId): array
    {
        if (! Schema::hasTable('college_academic_calendars')) return [];
        return DB::table('college_academic_calendars as cac')
            ->join('colleges as c', 'c.id', '=', 'cac.college_id')
            ->join('academic_calendars as ac', 'ac.id', '=', 'cac.university_academic_calendar_id')
            ->join('academic_sessions as s', 's.id', '=', 'ac.academic_session_id')
            ->where('c.university_id', $universityId)
            ->orderBy('c.name')->orderByDesc('s.starts_on')
            ->get(['cac.id','cac.status','c.name as college_name','ac.code as calendar_code','ac.name as calendar_name','s.name as session_name'])
            ->map(fn ($row) => [
                'id' => $row->id,
                'code' => $row->calendar_code,
                'name' => $row->college_name.' · '.$row->session_name.' · '.$row->calendar_name,
                'status' => $row->status,
                'kind' => 'COLLEGE_ACADEMIC_CALENDAR',
                'dependencies' => ['overrides' => $this->countIfExists('college_calendar_overrides', 'college_academic_calendar_id', $row->id)],
                'blocked' => false,
                'blocking_references' => [],
            ])->values()->all();
    }

    private function countCollegeAcademicCalendarsForUniversity(int $universityId): int
    {
        if (! Schema::hasTable('college_academic_calendars')) return 0;
        return (int) DB::table('college_academic_calendars as cac')->join('colleges as c','c.id','=','cac.college_id')->where('c.university_id',$universityId)->count();
    }

    private function countCollegeCalendarOverridesForUniversity(int $universityId): int
    {
        if (! Schema::hasTable('college_calendar_overrides') || ! Schema::hasTable('college_academic_calendars')) return 0;
        return (int) DB::table('college_calendar_overrides as cco')->join('college_academic_calendars as cac','cac.id','=','cco.college_academic_calendar_id')->join('colleges as c','c.id','=','cac.college_id')->where('c.university_id',$universityId)->count();
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
                        ['fee_late_fine_rules', 'college_program_offering_id'],
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
        int $actorId,
        ?array $after = null
    ): void {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('audit_logs')->insert([
            'event' => $event,
            'resource_type' => $type,
            'resource_id' => $id,
            'before' => json_encode($before),
            'after' => $after === null ? null : json_encode($after),
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

    private function countCollegeAdmissionApplicationChildForUniversity(string $table, int $universityId): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasTable('college_admission_applications')) {
            return 0;
        }

        return DB::table($table.' as child')
            ->join('college_admission_applications as a', 'a.id', '=', 'child.college_admission_application_id')
            ->join('colleges as c', 'c.id', '=', 'a.college_id')
            ->where('c.university_id', $universityId)
            ->count();
    }

    private function countCollegeScopedRowsForUniversity(string $table, int $universityId): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasTable('colleges')) {
            return 0;
        }

        return DB::table($table.' as scoped')
            ->join('colleges as c', 'c.id', '=', 'scoped.college_id')
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
