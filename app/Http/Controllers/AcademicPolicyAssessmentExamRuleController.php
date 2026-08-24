<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertAcademicPolicyAssessmentExamRuleRequest;
use App\Models\AcademicPolicy;
use App\Models\AcademicPolicyAssessmentExamRule;
use App\Services\AcademicPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcademicPolicyAssessmentExamRuleController extends Controller
{
    public function __construct(private readonly AcademicPolicyService $policyService) {}

    public function edit(Request $request, AcademicPolicy $academicPolicy): Response
    {
        abort_unless($request->user()->hasPermission('academic_policy.view'), 403);
        $academicPolicy->load(['academicSession:id,name,code','programTemplate:id,name,code','curriculum:id,name,code,version','assessmentExamRule']);

        return Inertia::render('admin/academic-policies/assessment-examination', [
            'policy' => $academicPolicy,
            'rule' => $academicPolicy->assessmentExamRule,
            'editable' => $request->user()->hasPermission('academic_policy.update') &&
                $academicPolicy->lifecycle_status === 'DRAFT' &&
                ! in_array($academicPolicy->approval_status ?? 'NOT_SUBMITTED', ['SUBMITTED','UNDER_APPROVAL','APPROVED'], true),
        ]);
    }

    public function update(UpsertAcademicPolicyAssessmentExamRuleRequest $request, AcademicPolicy $academicPolicy): RedirectResponse
    {
        $this->policyService->assertEditable($academicPolicy);
        $data = $request->validated();

        if (! $data['allow_grace_marks']) $data['maximum_grace_marks'] = null;
        elseif ($data['maximum_grace_marks'] === null) {
            throw ValidationException::withMessages(['maximum_grace_marks' => 'Maximum Grace Marks is required when grace marks are allowed.']);
        }

        DB::transaction(function () use ($academicPolicy, $data, $request) {
            $existing = AcademicPolicyAssessmentExamRule::query()->where('academic_policy_id', $academicPolicy->id)->first();
            $before = $existing?->toArray();
            $rule = AcademicPolicyAssessmentExamRule::query()->updateOrCreate(
                ['academic_policy_id' => $academicPolicy->id],
                [...$data, 'created_by' => $existing?->created_by ?? $request->user()->id, 'updated_by' => $request->user()->id]
            );
            $this->policyService->clearValidationCheckpoint($academicPolicy);
            DB::table('audit_logs')->insert([
                'event' => $existing ? 'ACADEMIC_POLICY_ASSESSMENT_EXAM_UPDATED' : 'ACADEMIC_POLICY_ASSESSMENT_EXAM_CREATED',
                'resource_type' => 'academic_policy_assessment_exam_rule', 'resource_id' => $rule->id,
                'before' => $before ? json_encode($before) : null, 'after' => json_encode($rule->fresh()->toArray()),
                'actor_user_id' => $request->user()->id, 'ip_address' => $request->ip(), 'created_at' => now(),
            ]);
        });
        return back()->with('success', 'Assessment / Examination Policy saved successfully.');
    }
}
