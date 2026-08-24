<?php

namespace App\Services;

use App\Models\AcademicPolicy;
use Illuminate\Support\Facades\DB;

class AcademicPolicyValidationService
{
    public function validateAndRecord(AcademicPolicy $policy, int $actorId): array
    {
        $result = $this->validate($policy);
        $hash = $result['valid'] ? $this->fingerprint($policy) : null;

        DB::transaction(function () use ($policy, $actorId, $result, $hash) {
            $policy->update([
                'validation_hash' => $hash,
                'validated_at' => $result['valid'] ? now() : null,
                'validated_by' => $result['valid'] ? $actorId : null,
            ]);

            DB::table('audit_logs')->insert([
                'event' => 'ACADEMIC_POLICY_VALIDATED',
                'resource_type' => 'academic_policy',
                'resource_id' => $policy->id,
                'before' => null,
                'after' => json_encode($result),
                'actor_user_id' => $actorId,
                'ip_address' => request()->ip(),
                'created_at' => now(),
            ]);
        });

        return $result;
    }

    public function validate(AcademicPolicy $policy): array
    {
        $errors = [];
        $warnings = [];
        $policy->loadMissing(['creditCompletionRule', 'creditCategoryRequirements.courseCategory', 'attendanceRule', 'assessmentExamRule', 'gradingRule', 'gradeBands', 'progressionRuleSets.sourceTerms', 'progressionRuleSets.targetTerm']);
        $rule = $policy->creditCompletionRule;

        if (! $rule) {
            $warnings[] = 'Credit / Completion Policy has not been configured. This section is optional where the institution does not require it.';
        } else {
            $categoryMinimumCredits = $policy->creditCategoryRequirements
                ->sum(fn ($requirement) => (float) $requirement->minimum_credits);

            if (
                $rule->minimum_total_credits !== null &&
                $categoryMinimumCredits > (float) $rule->minimum_total_credits + 0.0001
            ) {
                $errors[] = 'Sum of configured Course Category minimum credits exceeds Minimum Total Credits.';
            }

            foreach ($policy->creditCategoryRequirements as $requirement) {
                if (
                    $requirement->maximum_credits !== null &&
                    (float) $requirement->maximum_credits < (float) $requirement->minimum_credits
                ) {
                    $errors[] = sprintf(
                        '%s maximum credits cannot be lower than its minimum credits.',
                        $requirement->courseCategory?->name ?? 'Course Category'
                    );
                }
            }

            if ($rule->allow_credit_transfer && $rule->maximum_credit_transfer_percent === null) {
                $errors[] = 'Maximum Credit Transfer Percent is required when Credit Transfer is allowed.';
            }

            if (! $rule->allow_credit_transfer && $rule->maximum_credit_transfer_percent !== null) {
                $warnings[] = 'Credit Transfer is disabled, so Maximum Credit Transfer Percent will not be used.';
            }
        }



        $attendance = $policy->attendanceRule;
        if (! $attendance) {
            $warnings[] = 'Attendance Policy has not been configured. This section is optional until attendance rules are required for this policy.';
        } else {
            $minimumAttendance = (float) $attendance->minimum_attendance_percent;

            if ($attendance->allow_condonation) {
                if ($attendance->condonation_minimum_percent === null) {
                    $errors[] = 'Condonation Minimum Attendance is required when condonation is allowed.';
                } elseif ((float) $attendance->condonation_minimum_percent >= $minimumAttendance) {
                    $errors[] = 'Condonation Minimum Attendance must be lower than Minimum Attendance.';
                }

                if (
                    $attendance->maximum_condonable_shortage_percent !== null &&
                    (float) $attendance->maximum_condonable_shortage_percent > $minimumAttendance
                ) {
                    $errors[] = 'Maximum condonable shortage cannot exceed Minimum Attendance.';
                }
            } elseif (
                $attendance->condonation_minimum_percent !== null ||
                $attendance->maximum_condonable_shortage_percent !== null
            ) {
                $warnings[] = 'Condonation is disabled, so condonation thresholds will not be used.';
            }
        }

        $assessmentExam = $policy->assessmentExamRule;
        if (! $assessmentExam) {
            $warnings[] = 'Assessment / Examination Policy has not been configured. This section is optional until assessment/examination rules are required for this policy.';
        } else {
            if ($assessmentExam->allow_grace_marks && $assessmentExam->maximum_grace_marks === null) {
                $errors[] = 'Maximum Grace Marks is required when Grace Marks are allowed.';
            }
            if (! $assessmentExam->allow_grace_marks && $assessmentExam->maximum_grace_marks !== null) {
                $warnings[] = 'Grace Marks are disabled, so Maximum Grace Marks will not be used.';
            }
        }

        $grading = $policy->gradingRule;
        if (! $grading) {
            $warnings[] = 'Grading Policy has not been configured. This section is optional until grading rules are required for this policy.';
        } else {
            if ($grading->grading_basis !== 'PASS_FAIL' && $policy->gradeBands->isEmpty()) {
                $errors[] = 'At least one Grade Band is required for Letter Grade / Grade Point grading.';
            }
            if ($grading->grading_basis === 'GRADE_POINT' && $grading->maximum_grade_point === null) {
                $errors[] = 'Maximum Grade Point is required for Grade Point grading.';
            }
            $sortedBands = $policy->gradeBands->sortBy('minimum_percent')->values();
            for ($i = 1; $i < $sortedBands->count(); $i++) {
                if ((float) $sortedBands[$i]->minimum_percent <= (float) $sortedBands[$i - 1]->maximum_percent) {
                    $errors[] = 'Grade percentage ranges cannot overlap.';
                    break;
                }
            }
        }

        $progressionRuleSets = $policy->progressionRuleSets;
        if ($progressionRuleSets->isEmpty()) {
            $warnings[] = 'Promotion / Progression Policy has not been configured. This section is optional until progression rules are required for this policy.';
        } else {
            $defaultCount = $progressionRuleSets->where('applies_to_all_stages', true)->count();
            if ($defaultCount > 1) {
                $errors[] = 'Only one Default / All Stages Progression Rule is allowed per Academic Policy.';
            }

            foreach ($progressionRuleSets as $ruleSet) {
                if (! $ruleSet->applies_to_all_stages) {
                    if (! $ruleSet->curriculum_id) {
                        $errors[] = sprintf('%s must select a Curriculum.', $ruleSet->name);
                    }
                    if ($ruleSet->sourceTerms->isEmpty()) {
                        $errors[] = sprintf('%s must select at least one source Term / Semester.', $ruleSet->name);
                    }
                    if (! $ruleSet->target_curriculum_term_id) {
                        $errors[] = sprintf('%s must select a Progress To Term.', $ruleSet->name);
                    }

                    $foreignSource = $ruleSet->sourceTerms
                        ->contains(fn ($term) => (int) $term->curriculum_id !== (int) $ruleSet->curriculum_id);

                    if ($foreignSource) {
                        $errors[] = sprintf('%s contains a source Term outside its selected Curriculum.', $ruleSet->name);
                    }

                    if (
                        $ruleSet->targetTerm &&
                        (int) $ruleSet->targetTerm->curriculum_id !== (int) $ruleSet->curriculum_id
                    ) {
                        $errors[] = sprintf('%s target Term does not belong to its selected Curriculum.', $ruleSet->name);
                    }

                    if (
                        $ruleSet->target_curriculum_term_id &&
                        $ruleSet->sourceTerms->contains(
                            fn ($term) => (int) $term->id === (int) $ruleSet->target_curriculum_term_id
                        )
                    ) {
                        $errors[] = sprintf('%s target Term cannot also be a source Term.', $ruleSet->name);
                    }

                    if (
                        $ruleSet->evaluation_mode === 'COMBINED' &&
                        $ruleSet->sourceTerms->count() > 1 &&
                        $ruleSet->minimum_sgpa !== null
                    ) {
                        $errors[] = sprintf(
                            '%s is a combined multi-term rule; use Minimum CGPA instead of Minimum SGPA.',
                            $ruleSet->name
                        );
                    }
                }

                if (
                    ! $ruleSet->allow_carry_forward &&
                    $ruleSet->maximum_backlog_courses !== null &&
                    (int) $ruleSet->maximum_backlog_courses > 0
                ) {
                    $warnings[] = sprintf(
                        '%s has Carry Forward disabled; Maximum Backlog Courses remains a failure threshold, not permission to carry those backlogs.',
                        $ruleSet->name
                    );
                }
            }
        }

        if ($policy->effective_to && $policy->effective_from && $policy->effective_to->lt($policy->effective_from)) {
            $errors[] = 'Effective To cannot be earlier than Effective From.';
        }

        return ['valid' => count($errors) === 0, 'errors' => $errors, 'warnings' => $warnings];
    }

    public function hasCurrentValidCheckpoint(AcademicPolicy $policy): bool
    {
        if (! $policy->validation_hash || ! $policy->validated_at) return false;
        return hash_equals((string) $policy->validation_hash, $this->fingerprint($policy));
    }

    public function fingerprint(AcademicPolicy $policy): string
    {
        $policy->loadMissing(['creditCompletionRule', 'creditCategoryRequirements', 'attendanceRule', 'assessmentExamRule', 'gradingRule', 'gradeBands', 'progressionRuleSets.sourceTerms', 'progressionRuleSets.targetTerm']);

        return hash('sha256', json_encode([
            'header' => [
                'university_id' => $policy->university_id,
                'academic_session_id' => $policy->academic_session_id,
                'program_template_id' => $policy->program_template_id,
                'curriculum_id' => $policy->curriculum_id,
                'name' => $policy->name,
                'code' => $policy->code,
                'version' => $policy->version,
                'scope_type' => $policy->scope_type,
                'effective_from' => optional($policy->effective_from)->format('Y-m-d'),
                'effective_to' => optional($policy->effective_to)->format('Y-m-d'),
            ],
            'credit_completion' => $policy->creditCompletionRule?->only([
                'minimum_total_credits',
                'minimum_completion_cgpa',
                'maximum_program_duration_months',
                'allow_credit_transfer',
                'maximum_credit_transfer_percent',
                'allow_credit_exemption',
                'notes',
            ]),
            'credit_category_requirements' => $policy->creditCategoryRequirements
                ->map(fn ($requirement) => [
                    'course_category_id' => (int) $requirement->course_category_id,
                    'minimum_credits' => (string) $requirement->minimum_credits,
                    'maximum_credits' => $requirement->maximum_credits === null
                        ? null
                        : (string) $requirement->maximum_credits,
                    'display_order' => (int) $requirement->display_order,
                ])
                ->values()
                ->all(),
            'attendance' => $policy->attendanceRule?->only([
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
            'assessment_examination' => $policy->assessmentExamRule?->only([
                'minimum_overall_pass_percent',
                'require_separate_component_pass',
                'absence_result',
                'allow_grace_marks',
                'maximum_grace_marks',
                'allow_improvement_exam',
                'allow_supplementary_exam',
                'notes',
            ]),
            'grading' => $policy->gradingRule?->only([
                'grading_basis','maximum_grade_point','calculate_sgpa','calculate_cgpa',
                'sgpa_decimal_places','cgpa_decimal_places','rounding_rule','notes',
            ]),
            'grade_bands' => $policy->gradeBands->map(fn ($band) => [
                'minimum_percent'=>(string)$band->minimum_percent,'maximum_percent'=>(string)$band->maximum_percent,
                'grade_code'=>$band->grade_code,'grade_label'=>$band->grade_label,
                'grade_point'=>$band->grade_point === null ? null : (string)$band->grade_point,
                'is_passing'=>(bool)$band->is_passing,'display_order'=>(int)$band->display_order,
            ])->values()->all(),
            'progression_rule_sets' => $policy->progressionRuleSets->map(fn ($ruleSet) => [
                'curriculum_id' => $ruleSet->curriculum_id,
                'name' => $ruleSet->name,
                'applies_to_all_stages' => (bool) $ruleSet->applies_to_all_stages,
                'evaluation_mode' => $ruleSet->evaluation_mode,
                'source_term_ids' => $ruleSet->sourceTerms->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
                'target_curriculum_term_id' => $ruleSet->target_curriculum_term_id,
                'minimum_earned_credits' => $ruleSet->minimum_earned_credits === null ? null : (string) $ruleSet->minimum_earned_credits,
                'minimum_sgpa' => $ruleSet->minimum_sgpa === null ? null : (string) $ruleSet->minimum_sgpa,
                'minimum_cgpa' => $ruleSet->minimum_cgpa === null ? null : (string) $ruleSet->minimum_cgpa,
                'maximum_backlog_courses' => $ruleSet->maximum_backlog_courses,
                'mandatory_courses_must_be_passed' => (bool) $ruleSet->mandatory_courses_must_be_passed,
                'allow_carry_forward' => (bool) $ruleSet->allow_carry_forward,
                'allow_detention' => (bool) $ruleSet->allow_detention,
                'allow_year_back' => (bool) $ruleSet->allow_year_back,
                'allow_readmission' => (bool) $ruleSet->allow_readmission,
                'maximum_attempts_per_course' => $ruleSet->maximum_attempts_per_course,
                'display_order' => (int) $ruleSet->display_order,
                'notes' => $ruleSet->notes,
            ])->values()->all(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
