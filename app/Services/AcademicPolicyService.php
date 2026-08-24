<?php

namespace App\Services;

use App\Models\AcademicPolicy;
use App\Models\University;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicPolicyService
{
    public function create(University $university, array $data, int $actorId): AcademicPolicy
    {
        $this->validateScope($university->id, $data);
        $this->assertIdentityAvailable($university->id, $data);

        return DB::transaction(function () use ($university, $data, $actorId) {
            $policy = AcademicPolicy::create([
                ...$data,
                'university_id' => $university->id,
                'lifecycle_status' => 'DRAFT',
                'approval_status' => 'NOT_SUBMITTED',
                'is_current_version' => false,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit('ACADEMIC_POLICY_CREATED', $policy, null, $policy->toArray(), $actorId);
            return $policy;
        });
    }

    public function update(AcademicPolicy $policy, array $data, int $actorId): AcademicPolicy
    {
        $this->assertEditable($policy);
        $this->validateScope($policy->university_id, $data);
        $this->assertIdentityAvailable($policy->university_id, $data, $policy->id);

        return DB::transaction(function () use ($policy, $data, $actorId) {
            $before = $policy->toArray();
            $policy->fill([...$data, 'updated_by' => $actorId])->save();
            $policy->refresh();
            $this->clearValidationCheckpoint($policy);
            $this->audit('ACADEMIC_POLICY_UPDATED', $policy, $before, $policy->toArray(), $actorId);
            return $policy;
        });
    }


    public function cloneFullPolicy(
        AcademicPolicy $source,
        array $data,
        int $actorId
    ): AcademicPolicy {
        $name = trim((string) ($data['name'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        $version = trim((string) ($data['version'] ?? '1.0'));

        if ($name === '' || $code === '' || $version === '') {
            throw ValidationException::withMessages([
                'clone' => 'Policy name, code and version are required.',
            ]);
        }

        $targetSessionId = (int) ($data['academic_session_id'] ?? $source->academic_session_id);
        if (! DB::table('academic_sessions')->where('id', $targetSessionId)->where('university_id', $source->university_id)->exists()) { throw ValidationException::withMessages(['academic_session_id' => 'Selected Academic Session does not belong to this University.']); }

        $this->assertIdentityAvailable(
            $source->university_id,
            ['code' => $code, 'version' => $version]
        );

        return DB::transaction(function () use ($source, $data, $actorId, $name, $code, $version, $targetSessionId) {
            $source->load([
                'creditCompletionRule',
                'creditCategoryRequirements',
                'attendanceRule',
                'assessmentExamRule',
                'gradingRule',
                'gradeBands',
                'progressionRuleSets.sourceTerms',
            ]);

            $target = AcademicPolicy::create([
                'university_id' => $source->university_id,
                'academic_session_id' => $targetSessionId,
                'program_template_id' => $source->program_template_id,
                'curriculum_id' => $source->curriculum_id,
                'parent_policy_id' => null,
                'superseded_by_id' => null,
                'name' => $name,
                'code' => $code,
                'version' => $version,
                'scope_type' => $source->scope_type,
                'effective_from' => $data['effective_from'] ?? $source->effective_from,
                'effective_to' => $data['effective_to'] ?? $source->effective_to,
                'lifecycle_status' => 'DRAFT',
                'approval_status' => 'NOT_SUBMITTED',
                'is_current_version' => false,
                'revision_type' => 'CLONE',
                'revision_reason' => 'Full policy cloned from '.$source->code.' V'.$source->version,
                'validation_hash' => null,
                'validated_at' => null,
                'validated_by' => null,
                'description' => $source->description,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            if ($source->creditCompletionRule) {
                $target->creditCompletionRule()->create([
                    ...$source->creditCompletionRule->only(['minimum_total_credits','minimum_completion_cgpa','maximum_program_duration_months','allow_credit_transfer','maximum_credit_transfer_percent','allow_credit_exemption','notes']),
                    'created_by' => $actorId, 'updated_by' => $actorId,
                ]);
            }
            foreach ($source->creditCategoryRequirements as $row) {
                $target->creditCategoryRequirements()->create([
                    ...$row->only(['course_category_id','minimum_credits','maximum_credits','display_order']),
                    'created_by' => $actorId, 'updated_by' => $actorId,
                ]);
            }
            if ($source->attendanceRule) {
                $target->attendanceRule()->create([
                    ...$source->attendanceRule->only(['minimum_attendance_percent','calculation_level','allow_condonation','condonation_minimum_percent','maximum_condonable_shortage_percent','attendance_required_for_exam','allow_special_exemption','rounding_rule','notes']),
                    'created_by' => $actorId, 'updated_by' => $actorId,
                ]);
            }
            if ($source->assessmentExamRule) {
                $target->assessmentExamRule()->create([
                    ...$source->assessmentExamRule->only(['minimum_overall_pass_percent','require_separate_component_pass','absence_result','allow_grace_marks','maximum_grace_marks','allow_improvement_exam','allow_supplementary_exam','notes']),
                    'created_by' => $actorId, 'updated_by' => $actorId,
                ]);
            }
            if ($source->gradingRule) {
                $target->gradingRule()->create([
                    ...$source->gradingRule->only(['grading_basis','maximum_grade_point','calculate_sgpa','calculate_cgpa','sgpa_decimal_places','cgpa_decimal_places','rounding_rule','notes']),
                    'created_by' => $actorId, 'updated_by' => $actorId,
                ]);
            }
            foreach ($source->gradeBands as $band) {
                $target->gradeBands()->create($band->only(['minimum_percent','maximum_percent','grade_code','grade_label','grade_point','is_passing','display_order']));
            }
            foreach ($source->progressionRuleSets as $ruleSet) {
                $targetCurriculumId = $ruleSet->curriculum_id; $targetTermId = $ruleSet->target_curriculum_term_id; $termMap = collect();
                if (! $ruleSet->applies_to_all_stages && $targetSessionId !== (int) $source->academic_session_id) {
                    if ($source->scope_type === 'CURRICULUM') throw ValidationException::withMessages(['academic_session_id' => 'For a Curriculum-scoped Policy, changing Academic Session requires a corresponding target Curriculum.']);
                    $sourceCurriculum = DB::table('curricula')->where('id', $ruleSet->curriculum_id)->first(['program_template_id']);
                    $programTemplateId = $source->scope_type === 'PROGRAM_TEMPLATE' ? $source->program_template_id : ($sourceCurriculum->program_template_id ?? null);
                    $targetCurriculum = DB::table('curricula')->where('university_id', $source->university_id)->where('academic_session_id', $targetSessionId)->when($programTemplateId, fn ($q) => $q->where('program_template_id', $programTemplateId))->where('lifecycle_status', 'ACTIVE')->where('approval_status', 'APPROVED')->orderByDesc('id')->first(['id']);
                    if (! $targetCurriculum) throw ValidationException::withMessages(['academic_session_id' => 'Selected Academic Session has no current approved compatible Curriculum structure for progression mapping.']);
                    $targetCurriculumId = (int) $targetCurriculum->id; $termMap = DB::table('curriculum_terms')->where('curriculum_id', $targetCurriculumId)->where('status', 'ACTIVE')->get(['id','sequence_no'])->keyBy('sequence_no');
                    if ($ruleSet->target_curriculum_term_id) { $seq=DB::table('curriculum_terms')->where('id',$ruleSet->target_curriculum_term_id)->value('sequence_no'); $mapped=$termMap->get($seq); if(!$mapped) throw ValidationException::withMessages(['academic_session_id'=>'Target session Curriculum has no matching progression target term.']); $targetTermId=(int)$mapped->id; }
                }
                $newRuleSet=$target->progressionRuleSets()->create([...$ruleSet->only(['name','applies_to_all_stages','evaluation_mode','minimum_earned_credits','minimum_sgpa','minimum_cgpa','maximum_backlog_courses','mandatory_courses_must_be_passed','allow_carry_forward','allow_detention','allow_year_back','allow_readmission','maximum_attempts_per_course','display_order','notes']),'curriculum_id'=>$targetCurriculumId,'target_curriculum_term_id'=>$targetTermId,'created_by'=>$actorId,'updated_by'=>$actorId]);
                $sync=[]; foreach($ruleSet->sourceTerms as $term){$mappedId=$term->id;if($termMap->isNotEmpty()){$mapped=$termMap->get((int)$term->sequence_no);if(!$mapped)throw ValidationException::withMessages(['academic_session_id'=>"Target session Curriculum does not contain term sequence {$term->sequence_no} required by the cloned progression structure."]);$mappedId=(int)$mapped->id;}$sync[$mappedId]=['display_order'=>$term->pivot->display_order??0];}$newRuleSet->sourceTerms()->sync($sync);
            }

            $this->audit('ACADEMIC_POLICY_FULL_CLONED', $target, null, [
                'source_policy_id' => $source->id,
                'target_policy_id' => $target->id,
            ], $actorId);

            return $target;
        });
    }

    public function amend(
        AcademicPolicy $source,
        array $data,
        int $actorId
    ): AcademicPolicy {
        if (
            $source->lifecycle_status !== 'ACTIVE' ||
            $source->approval_status !== 'APPROVED' ||
            ! $source->is_current_version
        ) {
            throw ValidationException::withMessages([
                'academic_policy' =>
                    'Only the current ACTIVE and APPROVED Academic Policy can be amended.',
            ]);
        }

        $openAmendment = AcademicPolicy::query()
            ->where('parent_policy_id', $source->id)
            ->whereIn('approval_status', [
                'NOT_SUBMITTED',
                'RETURNED',
                'REJECTED',
                'SUBMITTED',
                'UNDER_APPROVAL',
            ])
            ->exists();

        if ($openAmendment) {
            throw ValidationException::withMessages([
                'academic_policy' =>
                    'An amendment is already open for this Academic Policy.',
            ]);
        }

        $reason = trim((string) ($data['revision_reason'] ?? ''));
        if ($reason === '') {
            throw ValidationException::withMessages([
                'revision_reason' => 'Amendment reason is required.',
            ]);
        }

        $newVersion = trim((string) ($data['version'] ?? ''));
        if ($newVersion === '') {
            $newVersion = $this->nextVersion($source->version);
        }

        $this->assertIdentityAvailable(
            $source->university_id,
            ['code' => $source->code, 'version' => $newVersion]
        );

        return DB::transaction(function () use (
            $source,
            $data,
            $actorId,
            $reason,
            $newVersion
        ) {
            $source->load([
                'creditCompletionRule',
                'creditCategoryRequirements',
                'attendanceRule',
                'assessmentExamRule',
                'gradingRule',
                'gradeBands',
                'progressionRuleSets.sourceTerms',
            ]);

            $target = AcademicPolicy::create([
                'university_id' => $source->university_id,
                'academic_session_id' => $source->academic_session_id,
                'program_template_id' => $source->program_template_id,
                'curriculum_id' => $source->curriculum_id,
                'parent_policy_id' => $source->id,
                'superseded_by_id' => null,
                'name' => $source->name,
                'code' => $source->code,
                'version' => $newVersion,
                'scope_type' => $source->scope_type,
                'effective_from' => $source->effective_from,
                'effective_to' => $source->effective_to,
                'lifecycle_status' => 'DRAFT',
                'approval_status' => 'NOT_SUBMITTED',
                'is_current_version' => false,
                'revision_type' => $data['revision_type'] ?? 'AMENDMENT',
                'revision_reason' => $reason,
                'revision_effective_from' =>
                    $data['revision_effective_from'] ?? null,
                'validation_hash' => null,
                'validated_at' => null,
                'validated_by' => null,
                'description' => $source->description,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            if ($source->creditCompletionRule) {
                $target->creditCompletionRule()->create([
                    ...$source->creditCompletionRule->only([
                        'minimum_total_credits',
                        'minimum_completion_cgpa',
                        'maximum_program_duration_months',
                        'allow_credit_transfer',
                        'maximum_credit_transfer_percent',
                        'allow_credit_exemption',
                        'notes',
                    ]),
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            }

            foreach ($source->creditCategoryRequirements as $row) {
                $target->creditCategoryRequirements()->create([
                    ...$row->only([
                        'course_category_id',
                        'minimum_credits',
                        'maximum_credits',
                        'display_order',
                    ]),
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            }

            if ($source->attendanceRule) {
                $target->attendanceRule()->create([
                    ...$source->attendanceRule->only([
                        'minimum_attendance_percent',
                        'calculation_level',
                        'allow_condonation',
                        'condonation_minimum_percent',
                        'maximum_condonable_shortage_percent',
                        'attendance_required_for_exam',
                        'allow_special_exemption',
                        'rounding_rule',
                        'notes',
                    ]),
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            }

            if ($source->assessmentExamRule) {
                $target->assessmentExamRule()->create([
                    ...$source->assessmentExamRule->only([
                        'minimum_overall_pass_percent',
                        'require_separate_component_pass',
                        'absence_result',
                        'allow_grace_marks',
                        'maximum_grace_marks',
                        'allow_improvement_exam',
                        'allow_supplementary_exam',
                        'notes',
                    ]),
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            }

            if ($source->gradingRule) {
                $target->gradingRule()->create([
                    ...$source->gradingRule->only([
                        'grading_basis',
                        'maximum_grade_point',
                        'calculate_sgpa',
                        'calculate_cgpa',
                        'sgpa_decimal_places',
                        'cgpa_decimal_places',
                        'rounding_rule',
                        'notes',
                    ]),
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            }

            foreach ($source->gradeBands as $band) {
                $target->gradeBands()->create(
                    $band->only([
                        'minimum_percent',
                        'maximum_percent',
                        'grade_code',
                        'grade_label',
                        'grade_point',
                        'is_passing',
                        'display_order',
                    ])
                );
            }

            foreach ($source->progressionRuleSets as $ruleSet) {
                $newRuleSet = $target->progressionRuleSets()->create([
                    ...$ruleSet->only([
                        'curriculum_id',
                        'name',
                        'applies_to_all_stages',
                        'evaluation_mode',
                        'target_curriculum_term_id',
                        'minimum_earned_credits',
                        'minimum_sgpa',
                        'minimum_cgpa',
                        'maximum_backlog_courses',
                        'mandatory_courses_must_be_passed',
                        'allow_carry_forward',
                        'allow_detention',
                        'allow_year_back',
                        'allow_readmission',
                        'maximum_attempts_per_course',
                        'display_order',
                        'notes',
                    ]),
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);

                $sync = [];
                foreach ($ruleSet->sourceTerms as $term) {
                    $sync[$term->id] = [
                        'display_order' =>
                            $term->pivot->display_order ?? 0,
                    ];
                }
                $newRuleSet->sourceTerms()->sync($sync);
            }

            $this->audit(
                'ACADEMIC_POLICY_AMENDMENT_CREATED',
                $target,
                null,
                [
                    'source_policy_id' => $source->id,
                    'new_policy_id' => $target->id,
                    'version' => $newVersion,
                    'revision_reason' => $reason,
                ],
                $actorId
            );

            return $target;
        });
    }

    private function nextVersion(string $version): string
    {
        if (preg_match('/^(\d+)\.(\d+)$/', trim($version), $match)) {
            return $match[1].'.'.((int) $match[2] + 1);
        }

        if (preg_match('/^(\d+)$/', trim($version), $match)) {
            return $match[1].'.1';
        }

        return trim($version).'.1';
    }

    public function assertEditable(AcademicPolicy $policy): void
    {
        if (
            $policy->lifecycle_status !== 'DRAFT' ||
            in_array($policy->approval_status ?? 'NOT_SUBMITTED', ['SUBMITTED', 'UNDER_APPROVAL', 'APPROVED'], true)
        ) {
            throw ValidationException::withMessages([
                'academic_policy' => 'Academic Policy is read-only while under approval, approved, active or retired.',
            ]);
        }
    }

    public function clearValidationCheckpoint(AcademicPolicy $policy): void
    {
        $policy->forceFill([
            'validation_hash' => null,
            'validated_at' => null,
            'validated_by' => null,
        ])->saveQuietly();
    }

    private function validateScope(int $universityId, array $data): void
    {
        $sessionExists = DB::table('academic_sessions')
            ->where('id', $data['academic_session_id'])
            ->where('university_id', $universityId)
            ->exists();

        if (! $sessionExists) {
            throw ValidationException::withMessages(['academic_session_id' => 'Academic Session does not belong to this University.']);
        }

        $scope = $data['scope_type'];
        $programId = $data['program_template_id'] ?? null;
        $curriculumId = $data['curriculum_id'] ?? null;

        if ($scope === 'UNIVERSITY' && ($programId || $curriculumId)) {
            throw ValidationException::withMessages(['scope_type' => 'University-wide policy cannot target a Program Template or Curriculum.']);
        }

        if ($scope === 'PROGRAM_TEMPLATE') {
            if (! $programId) {
                throw ValidationException::withMessages(['program_template_id' => 'Program Template is required for Program-scoped policy.']);
            }
            $exists = DB::table('program_templates')->where('id', $programId)->where('university_id', $universityId)->exists();
            if (! $exists) {
                throw ValidationException::withMessages(['program_template_id' => 'Program Template does not belong to this University.']);
            }
            if ($curriculumId) {
                throw ValidationException::withMessages(['curriculum_id' => 'Curriculum must be blank for Program-scoped policy.']);
            }
        }

        if ($scope === 'CURRICULUM') {
            if (! $curriculumId) {
                throw ValidationException::withMessages(['curriculum_id' => 'Curriculum is required for Curriculum-scoped policy.']);
            }
            $curriculum = DB::table('curricula')
                ->where('id', $curriculumId)
                ->where('university_id', $universityId)
                ->where('lifecycle_status', 'ACTIVE')
                ->where('approval_status', 'APPROVED')
                ->whereNotExists(function ($query) use ($curriculumId) {
                    $query->selectRaw('1')
                        ->from('curricula as approved_successor')
                        ->whereColumn(
                            'approved_successor.parent_curriculum_id',
                            'curricula.id'
                        )
                        ->where('approved_successor.approval_status', 'APPROVED');
                })
                ->first(['program_template_id', 'academic_session_id']);
            if (! $curriculum) {
                throw ValidationException::withMessages([
                    'curriculum_id' =>
                        'Select the current ACTIVE and APPROVED Curriculum version. Previous or superseded versions cannot be used for a new Curriculum-scoped Academic Policy.',
                ]);
            }
            if ((int) $curriculum->academic_session_id !== (int) $data['academic_session_id']) {
                throw ValidationException::withMessages(['curriculum_id' => 'Curriculum and Academic Policy must use the same Academic Session.']);
            }
            if ($programId && (int) $curriculum->program_template_id !== (int) $programId) {
                throw ValidationException::withMessages(['program_template_id' => 'Program Template does not match the selected Curriculum.']);
            }
        }
    }

    private function assertIdentityAvailable(int $universityId, array $data, ?int $ignoreId = null): void
    {
        $query = AcademicPolicy::query()
            ->where('university_id', $universityId)
            ->where('code', trim($data['code']))
            ->where('version', trim($data['version']));
        if ($ignoreId) $query->whereKeyNot($ignoreId);
        if ($query->exists()) {
            throw ValidationException::withMessages(['code' => 'This Policy code and version already exists.']);
        }
    }

    private function audit(string $event, AcademicPolicy $policy, ?array $before, ?array $after, int $actorId): void
    {
        DB::table('audit_logs')->insert([
            'event' => $event,
            'resource_type' => 'academic_policy',
            'resource_id' => $policy->id,
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'actor_user_id' => $actorId,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
