<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcademicPolicyRequest;
use App\Http\Requests\UpdateAcademicPolicyRequest;
use App\Models\AcademicPolicy;
use App\Models\AcademicSession;
use App\Models\Curriculum;
use App\Models\DegreeLevel;
use App\Models\ProgramTemplate;
use App\Models\University;
use App\Services\AcademicPolicyApprovalService;
use App\Services\AcademicPolicyService;
use App\Services\AcademicPolicyValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AcademicPolicyController extends Controller
{
    public function __construct(
        private readonly AcademicPolicyService $service,
        private readonly AcademicPolicyValidationService $validationService,
        private readonly AcademicPolicyApprovalService $approvalService,
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('academic_policy.view'), 403);
        $university = University::query()->firstOrFail();
        $search = trim((string) $request->query('search', ''));
        $status = strtoupper((string) $request->query('status', ''));

        $policies = AcademicPolicy::query()
            ->with(['academicSession:id,name,code', 'degreeLevel:id,name,code', 'programTemplate:id,name,code', 'curriculum:id,name,code,version'])
            ->withExists([
                'creditCompletionRule as credit_completion_configured',
                'attendanceRule as attendance_configured',
                'assessmentExamRule as assessment_exam_configured',
                'gradingRule as grading_configured',
                'progressionRuleSets as progression_configured',
            ])
            ->where('university_id', $university->id)
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('version', 'like', "%{$search}%")))
            ->when(in_array($status, ['DRAFT', 'ACTIVE', 'RETIRED'], true), fn ($q) => $q->where('lifecycle_status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $canSubmitApproval = $request->user()->hasPermission('approval_request.submit');
        $canUpdatePolicy = $request->user()->hasPermission('academic_policy.update');

        $activeApprovalWorkflows = DB::table('approval_workflows')
            ->where('university_id', $university->id)
            ->where('applies_to', 'ACADEMIC_POLICY')
            ->where('status', 'ACTIVE')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('approval_workflow_stages')
                    ->whereColumn(
                        'approval_workflow_stages.approval_workflow_id',
                        'approval_workflows.id'
                    )
                    ->where('approval_workflow_stages.status', 'ACTIVE');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'applies_to']);

        $policies->getCollection()->transform(
            function (AcademicPolicy $policy) use (
                $canSubmitApproval,
                $canUpdatePolicy,
                $activeApprovalWorkflows
            ) {
                $validationCurrent =
                    $this->validationService->hasCurrentValidCheckpoint($policy);

                $approvalEligible = in_array(
                    $policy->approval_status ?? 'NOT_SUBMITTED',
                    ['NOT_SUBMITTED', 'RETURNED', 'REJECTED'],
                    true
                );

                $openAmendment = AcademicPolicy::query()
                    ->where('parent_policy_id', $policy->id)
                    ->whereIn('approval_status', [
                        'NOT_SUBMITTED',
                        'RETURNED',
                        'REJECTED',
                        'SUBMITTED',
                        'UNDER_APPROVAL',
                    ])
                    ->exists();

                $policy->setAttribute('validation_current', $validationCurrent);
                $policy->setAttribute(
                    'can_submit_for_approval',
                    $canSubmitApproval
                    && $activeApprovalWorkflows->isNotEmpty()
                    && $policy->lifecycle_status === 'DRAFT'
                    && $approvalEligible
                    && $validationCurrent
                );
                $policy->setAttribute(
                    'can_amend',
                    $canUpdatePolicy
                    && $policy->lifecycle_status === 'ACTIVE'
                    && $policy->approval_status === 'APPROVED'
                    && (bool) $policy->is_current_version
                    && ! $openAmendment
                );

                // Version label contract:
                // Current = approved/current version.
                // Previous = a version that has actually been superseded by a newer version
                // in the same policy chain. Independent clones/drafts must not be labelled Previous.
                $policy->setAttribute(
                    'is_previous_version',
                    ! (bool) $policy->is_current_version
                    && ! is_null($policy->superseded_by_id)
                );

                $policy->setAttribute(
                    'amendment_of_version',
                    $policy->parent_policy_id
                        ? AcademicPolicy::query()
                            ->whereKey($policy->parent_policy_id)
                            ->value('version')
                        : null
                );

                return $policy;
            }
        );

        return Inertia::render('admin/academic-policies/index', [
            'policies' => $policies,
            'filters' => ['search' => $search, 'status' => $status],
            'academicSessions' => AcademicSession::query()->where('university_id', $university->id)->where('status', 'ACTIVE')->orderByDesc('starts_on')->get(['id','name','code']),
            'degreeLevels' => DegreeLevel::query()->where('university_id', $university->id)->where('status', 'ACTIVE')->orderBy('display_order')->orderBy('name')->get(['id','name','code']),
            'programTemplates' => ProgramTemplate::query()->where('university_id', $university->id)->where('status', 'ACTIVE')->orderBy('name')->get(['id','name','code']),
            'curricula' => Curriculum::query()
                ->where('university_id', $university->id)
                ->where('lifecycle_status', 'ACTIVE')
                ->where('approval_status', 'APPROVED')
                ->whereDoesntHave('amendments', fn ($query) =>
                    $query->where('approval_status', 'APPROVED')
                )
                ->orderBy('name')
                ->orderByDesc('id')
                ->get([
                    'id',
                    'name',
                    'code',
                    'version',
                    'program_template_id',
                    'academic_session_id',
                ]),
            'approvalWorkflows' => $activeApprovalWorkflows,
            'permissions' => [
                'create' => $request->user()->hasPermission('academic_policy.create'),
                'update' => $request->user()->hasPermission('academic_policy.update'),
                'disable' => $request->user()->hasPermission('academic_policy.disable'),
                'submitApproval' => $canSubmitApproval,
                'viewApproval' => $request->user()->hasPermission('approval_request.view'),
            ],
        ]);
    }

    public function store(StoreAcademicPolicyRequest $request): RedirectResponse
    {
        $university = University::query()->firstOrFail();
        $this->service->create($university, $request->validated(), $request->user()->id);
        return back()->with('success', 'Academic Policy created successfully.');
    }

    public function update(UpdateAcademicPolicyRequest $request, AcademicPolicy $academicPolicy): RedirectResponse
    {
        $this->service->update($academicPolicy, $request->validated(), $request->user()->id);
        return back()->with('success', 'Academic Policy updated successfully.');
    }

    public function submitForApproval(
        Request $request,
        AcademicPolicy $academicPolicy
    ): RedirectResponse {
        abort_unless(
            $request->user()->hasPermission('approval_request.submit'),
            403
        );

        $validated = $request->validate([
            'approval_workflow_id' => ['required', 'integer'],
        ]);

        $this->approvalService->submit(
            $academicPolicy,
            (int) $validated['approval_workflow_id'],
            $request->user()->id
        );

        return back()->with(
            'success',
            'Academic Policy submitted for approval.'
        );
    }

    public function cloneFullPolicy(
        Request $request,
        AcademicPolicy $academicPolicy
    ): RedirectResponse {
        abort_unless($request->user()->hasPermission('academic_policy.create'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'code' => ['required', 'string', 'max:100'],
            'version' => ['required', 'string', 'max:30'],
            'academic_session_id' => ['required', 'integer'],
            'effective_from' => ['nullable', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $clone = $this->service->cloneFullPolicy(
            $academicPolicy,
            $validated,
            $request->user()->id
        );

        return redirect()->route('academic-policies.index')->with(
            'success',
            "Full Academic Policy cloned as {$clone->code} V{$clone->version}. Review and validate before approval."
        );
    }

    public function amend(
        Request $request,
        AcademicPolicy $academicPolicy
    ): RedirectResponse {
        abort_unless(
            $request->user()->hasPermission('academic_policy.update'),
            403
        );

        $validated = $request->validate([
            'version' => ['nullable', 'string', 'max:30'],
            'revision_type' => ['nullable', 'string', 'max:50'],
            'revision_reason' => ['required', 'string', 'max:5000'],
            'revision_effective_from' => ['nullable', 'date'],
        ]);

        $amendment = $this->service->amend(
            $academicPolicy,
            $validated,
            $request->user()->id
        );

        return redirect()
            ->route('academic-policies.index')
            ->with(
                'success',
                "Academic Policy amendment V{$amendment->version} created as Draft."
            );
    }

    public function validatePolicy(Request $request, AcademicPolicy $academicPolicy): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('academic_policy.update'), 403);
        $this->service->assertEditable($academicPolicy);
        $result = $this->validationService->validateAndRecord($academicPolicy, $request->user()->id);

        return back()->with(
            $result['valid'] ? 'success' : 'error',
            $result['valid'] ? 'Academic Policy validation passed.' : implode(' ', $result['errors'])
        );
    }
}
