<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloneCurriculumStructureRequest;
use App\Http\Requests\StoreCurriculumRequest;
use App\Http\Requests\StoreCurriculumAmendmentRequest;
use App\Http\Requests\SubmitCurriculumApprovalRequest;
use App\Http\Requests\UpdateCurriculumRequest;
use App\Models\AcademicSession;
use App\Models\ApprovalWorkflow;
use App\Models\Curriculum;
use App\Models\ProgramTemplate;
use App\Models\University;
use App\Services\CurriculumCloneService;
use App\Services\CurriculumAmendmentService;
use App\Services\ApprovalRequestService;
use App\Services\CurriculumService;
use App\Services\CurriculumStructureDeleteService;
use App\Services\CurriculumStructureValidationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CurriculumController extends Controller
{
    public function __construct(
        private readonly CurriculumService $service,
        private readonly CurriculumCloneService $cloneService,
        private readonly CurriculumAmendmentService $amendmentService,
        private readonly ApprovalRequestService $approvalRequestService,
        private readonly CurriculumStructureDeleteService $deleteService,
        private readonly CurriculumStructureValidationService $structureValidationService
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('curriculum.view'), 403);

        $university = University::query()->firstOrFail();
        $search = trim((string) $request->query('search', ''));
        $status = strtoupper((string) $request->query('status', ''));

        $curricula = Curriculum::query()
            ->with([
                'programTemplate:id,name,code',
                'academicSession:id,name,code',
                'parentCurriculum:id,code,version',
            ])
            ->withExists([
                'amendments as has_approved_successor' => fn ($query) =>
                    $query->where('approval_status', 'APPROVED'),
                'amendments as has_open_amendment' => fn ($query) =>
                    $query->where('lifecycle_status', '!=', 'RETIRED'),
            ])
            ->where('university_id', $university->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('version', 'like', "%{$search}%");
                });
            })
            ->when(in_array($status, ['DRAFT', 'ACTIVE', 'RETIRED'], true), fn ($query) => $query->where('lifecycle_status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $canSubmitApproval =
            $request->user()->hasPermission('approval_request.submit');

        $hasActiveApprovalWorkflow = ApprovalWorkflow::query()
            ->where('university_id', $university->id)
            ->where('applies_to', 'CURRICULUM')
            ->where('status', 'ACTIVE')
            ->whereHas(
                'stages',
                fn ($query) => $query->where('status', 'ACTIVE')
            )
            ->exists();

        $canAmend = $request->user()->hasPermission('curriculum.update');

        $curricula->getCollection()->transform(
            function (Curriculum $curriculum) use (
                $canSubmitApproval,
                $hasActiveApprovalWorkflow,
                $canAmend
            ) {
                $validationCurrent =
                    $this->structureValidationService
                        ->hasCurrentValidCheckpoint($curriculum);

                $approvalEligible = in_array(
                    $curriculum->approval_status ?? 'NOT_SUBMITTED',
                    ['NOT_SUBMITTED', 'RETURNED', 'REJECTED'],
                    true
                );

                $curriculum->setAttribute(
                    'structure_validation_current',
                    $validationCurrent
                );

                $approvedActive =
                    $curriculum->lifecycle_status === 'ACTIVE' &&
                    ($curriculum->approval_status ?? 'NOT_SUBMITTED') === 'APPROVED';

                $hasApprovedSuccessor =
                    (bool) $curriculum->getAttribute('has_approved_successor');
                $hasOpenAmendment =
                    (bool) $curriculum->getAttribute('has_open_amendment');
                $isCurrentVersion = $approvedActive && ! $hasApprovedSuccessor;

                $curriculum->setAttribute(
                    'is_current_version',
                    $isCurrentVersion
                );
                $curriculum->setAttribute(
                    'is_previous_version',
                    $approvedActive && $hasApprovedSuccessor
                );
                $curriculum->setAttribute(
                    'can_amend',
                    $canAmend && $isCurrentVersion && ! $hasOpenAmendment
                );
                $curriculum->setAttribute(
                    'amendment_hint',
                    ! $approvedActive
                        ? null
                        : (
                            $hasApprovedSuccessor
                                ? 'A later approved version exists. Amend the current version instead.'
                                : (
                                    $hasOpenAmendment
                                        ? 'An amendment already exists for this version.'
                                        : null
                                )
                        )
                );

                $curriculum->setAttribute(
                    'can_submit_for_approval',
                    $canSubmitApproval &&
                    $hasActiveApprovalWorkflow &&
                    $curriculum->lifecycle_status === 'DRAFT' &&
                    $approvalEligible &&
                    $validationCurrent
                );

                $curriculum->setAttribute(
                    'submit_approval_hint',
                    ! $canSubmitApproval
                        ? 'Submit approval permission is required.'
                        : (
                            ! $hasActiveApprovalWorkflow
                                ? 'Configure an active approval workflow first.'
                                : (
                                    ! $validationCurrent
                                        ? 'Validate Structure first.'
                                        : null
                                )
                        )
                );

                return $curriculum;
            }
        );

        return Inertia::render('admin/curricula/index', [
            'curricula' => $curricula,
            'filters' => ['search' => $search, 'status' => $status],
            'programTemplates' => ProgramTemplate::query()
                ->where('university_id', $university->id)
                ->where('status', 'ACTIVE')
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'academicSessions' => AcademicSession::query()
                ->where('university_id', $university->id)
                ->where('status', 'ACTIVE')
                ->orderByDesc('starts_on')
                ->get(['id', 'name', 'code']),
            'approvalWorkflows' => ApprovalWorkflow::query()
                ->where('university_id', $university->id)
                ->where('applies_to', 'CURRICULUM')
                ->where('status', 'ACTIVE')
                ->whereHas('stages', fn ($query) =>
                    $query->where('status', 'ACTIVE')
                )
                ->orderBy('name')
                ->get(['id', 'name', 'code']),
            'permissions' => [
                'create' => $request->user()->hasPermission('curriculum.create'),
                'update' => $request->user()->hasPermission('curriculum.update'),
                'disable' => $request->user()->hasPermission('curriculum.disable'),
                'submitApproval' => $request->user()->hasPermission('approval_request.submit'),
            ],
        ]);
    }

    public function store(StoreCurriculumRequest $request): RedirectResponse
    {
        $university = University::query()->firstOrFail();
        $this->service->create($university, $request->validated(), $request->user()->id);

        return back()->with('success', 'Curriculum header created successfully.');
    }

    public function update(UpdateCurriculumRequest $request, Curriculum $curriculum): RedirectResponse
    {
        $this->service->update($curriculum, $request->validated(), $request->user()->id);

        return back()->with('success', 'Curriculum header updated successfully.');
    }

    public function retire(Request $request, Curriculum $curriculum): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('curriculum.disable'), 403);
        $this->service->retire($curriculum, $request->user()->id);

        return back()->with('success', 'Curriculum retired successfully.');
    }

    public function restore(Request $request, Curriculum $curriculum): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('curriculum.disable'), 403);

        $this->service->restore($curriculum, $request->user()->id);

        return back()->with('success', 'Curriculum restored successfully.');
    }


    public function amend(
        StoreCurriculumAmendmentRequest $request,
        Curriculum $curriculum
    ): RedirectResponse {
        $amendment = $this->amendmentService->create(
            $curriculum,
            $request->validated(),
            $request->user()->id
        );

        return redirect()
            ->route('curricula.structure.terms', $amendment)
            ->with(
                'success',
                'Curriculum amendment created as an editable Draft. Make the required changes, Validate Structure, then submit it for approval.'
            );
    }


    public function cloneStructure(
        CloneCurriculumStructureRequest $request,
        Curriculum $curriculum
    ): RedirectResponse {
        $target = $this->cloneService->cloneEntireCurriculum(
            $curriculum,
            $request->validated(),
            $request->user()->id
        );

        return redirect()
            ->route('curricula.structure.terms', $target)
            ->with(
                'success',
                'Curriculum and complete structure cloned successfully as a new Draft.'
            );
    }


    public function destroy(
        Request $request,
        Curriculum $curriculum
    ): RedirectResponse {
        abort_unless(
            $request->user()->hasPermission('curriculum.update'),
            403
        );

        $this->deleteService->deleteCurriculum(
            $curriculum,
            $request->user()->id
        );

        return redirect()
            ->route('curricula.index')
            ->with(
                'success',
                'Draft Curriculum and its complete structure deleted successfully.'
            );
    }


    public function submitForApproval(
        SubmitCurriculumApprovalRequest $request,
        Curriculum $curriculum
    ): RedirectResponse {
        $workflow = ApprovalWorkflow::query()
            ->findOrFail(
                (int) $request->validated('approval_workflow_id')
            );

        $this->approvalRequestService->submitCurriculum(
            $curriculum,
            $workflow,
            $request->user()->id
        );

        return back()->with(
            'success',
            'Curriculum submitted for academic approval.'
        );
    }

}
