<?php

namespace App\Http\Controllers;

use App\Services\AcademicPolicyApprovalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AcademicPolicyApprovalController extends Controller
{
    public function __construct(
        private readonly AcademicPolicyApprovalService $approvalService
    ) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('approval_request.view'), 403);

        $roleIds = DB::table('user_roles')
            ->where('user_id', $request->user()->id)
            ->where('status', 'ACTIVE')
            ->where(function ($query) {
                $query->whereNull('effective_from')->orWhere('effective_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('effective_until')->orWhere('effective_until', '>=', now());
            })
            ->pluck('role_id');

        $requests = DB::table('approval_requests as ar')
            ->join('academic_policies as ap', function ($join) {
                $join->on('ap.id', '=', 'ar.subject_id')
                    ->where('ar.subject_type', '=', 'ACADEMIC_POLICY');
            })
            ->leftJoin('approval_request_stages as ars', function ($join) {
                $join->on('ars.approval_request_id', '=', 'ar.id')
                    ->on('ars.sequence_no', '=', 'ar.current_stage_sequence');
            })
            ->leftJoin('approval_workflows as aw', 'aw.id', '=', 'ar.approval_workflow_id')
            ->select([
                'ar.id',
                'ar.status',
                'ar.current_stage_sequence',
                'ar.submitted_at',
                'ap.id as policy_id',
                'ap.name as policy_name',
                'ap.code as policy_code',
                'ap.version as policy_version',
                'ap.scope_type',
                'ap.approval_status as policy_approval_status',
                'ars.id as current_stage_id',
                'ars.name as current_stage_name',
                'ars.approver_role_id',
                'ars.remarks_required_on_reject',
                'ars.remarks_required_on_return',
                'aw.name as workflow_name',
            ])
            ->where('ar.subject_type', 'ACADEMIC_POLICY')
            ->orderByRaw("CASE WHEN ar.status = 'PENDING' THEN 0 ELSE 1 END")
            ->orderByDesc('ar.submitted_at')
            ->paginate(30)
            ->withQueryString();

        $requests->getCollection()->transform(function ($row) use ($roleIds, $request) {
            $row->can_decide =
                $request->user()->hasPermission('approval_request.decide')
                && $row->status === 'PENDING'
                && $row->approver_role_id
                && $roleIds->contains((int) $row->approver_role_id);

            $row->stages = DB::table('approval_request_stages')
                ->where('approval_request_id', $row->id)
                ->orderBy('sequence_no')
                ->get([
                    'sequence_no',
                    'name',
                    'status',
                    'remarks',
                    'decided_at',
                ]);

            return $row;
        });

        return Inertia::render('admin/academic-policies/approval-inbox', [
            'requests' => $requests,
        ]);
    }

    public function decide(Request $request, int $approvalRequest): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('approval_request.decide'), 403);

        $validated = $request->validate([
            'decision' => ['required', 'in:APPROVE,RETURN,REJECT'],
            'remarks' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->approvalService->decide(
            $approvalRequest,
            $request->user()->id,
            $validated['decision'],
            $validated['remarks'] ?? null
        );

        return back()->with('success', 'Academic Policy approval decision recorded.');
    }
}
