<?php

namespace App\Http\Controllers;

use App\Http\Requests\DecideApprovalRequest;
use App\Models\ApprovalRequest;
use App\Services\AcademicPolicyApprovalService;
use App\Services\ApprovalRequestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalRequestController extends Controller
{
    private const SUPPORTED_SUBJECT_TYPES = [
        'CURRICULUM',
        'ACADEMIC_POLICY',
    ];

    public function index(Request $request): Response
    {
        abort_unless(
            $request->user()->hasPermission('approval_request.view'),
            403
        );

        $userId = $request->user()->id;
        $now = now();

        $activeRoleIds = DB::table('user_roles')
            ->where('user_id', $userId)
            ->where('status', 'ACTIVE')
            ->where(function ($query) use ($now) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $now);
            })
            ->pluck('role_id');

        $pending = DB::table('approval_requests as ar')
            ->join('approval_request_stages as ars', function ($join) {
                $join->on(
                    'ars.approval_request_id',
                    '=',
                    'ar.id'
                )->on(
                    'ars.sequence_no',
                    '=',
                    'ar.current_stage_sequence'
                );
            })
            ->join(
                'approval_workflows as aw',
                'aw.id',
                '=',
                'ar.approval_workflow_id'
            )
            ->join('roles as r', 'r.id', '=', 'ars.approver_role_id')
            ->leftJoin('curricula as c', function ($join) {
                $join->on('c.id', '=', 'ar.subject_id')
                    ->where('ar.subject_type', '=', 'CURRICULUM');
            })
            ->leftJoin('academic_policies as ap', function ($join) {
                $join->on('ap.id', '=', 'ar.subject_id')
                    ->where('ar.subject_type', '=', 'ACADEMIC_POLICY');
            })
            ->whereIn('ar.subject_type', self::SUPPORTED_SUBJECT_TYPES)
            ->where('ar.status', 'PENDING')
            ->where('ars.status', 'PENDING')
            ->whereIn('ars.approver_role_id', $activeRoleIds)
            ->orderBy('ar.submitted_at')
            ->get([
                'ar.id',
                'ar.subject_type',
                'ar.subject_id',
                'ar.submitted_at',
                'ar.current_stage_sequence',
                DB::raw(
                    "CASE
                        WHEN ar.subject_type = 'CURRICULUM' THEN c.name
                        WHEN ar.subject_type = 'ACADEMIC_POLICY' THEN ap.name
                    END as subject_name"
                ),
                DB::raw(
                    "CASE
                        WHEN ar.subject_type = 'CURRICULUM' THEN c.code
                        WHEN ar.subject_type = 'ACADEMIC_POLICY' THEN ap.code
                    END as subject_code"
                ),
                DB::raw(
                    "CASE
                        WHEN ar.subject_type = 'CURRICULUM' THEN c.version
                        WHEN ar.subject_type = 'ACADEMIC_POLICY' THEN ap.version
                    END as subject_version"
                ),
                'aw.name as workflow_name',
                'aw.applies_to as workflow_applies_to',
                'ars.name as stage_name',
                'ars.approver_role_id',
                'r.name as approver_role_name',
            ]);

        $history = DB::table('approval_requests as ar')
            ->join(
                'approval_workflows as aw',
                'aw.id',
                '=',
                'ar.approval_workflow_id'
            )
            ->leftJoin(
                'users as submitter',
                'submitter.id',
                '=',
                'ar.submitted_by'
            )
            ->leftJoin('curricula as c', function ($join) {
                $join->on('c.id', '=', 'ar.subject_id')
                    ->where('ar.subject_type', '=', 'CURRICULUM');
            })
            ->leftJoin('academic_policies as ap', function ($join) {
                $join->on('ap.id', '=', 'ar.subject_id')
                    ->where('ar.subject_type', '=', 'ACADEMIC_POLICY');
            })
            ->whereIn('ar.subject_type', self::SUPPORTED_SUBJECT_TYPES)
            ->orderByDesc('ar.id')
            ->limit(100)
            ->get([
                'ar.id',
                'ar.subject_type',
                'ar.subject_id',
                'ar.status',
                'ar.submitted_at',
                'ar.completed_at',
                DB::raw(
                    "CASE
                        WHEN ar.subject_type = 'CURRICULUM' THEN c.name
                        WHEN ar.subject_type = 'ACADEMIC_POLICY' THEN ap.name
                    END as subject_name"
                ),
                DB::raw(
                    "CASE
                        WHEN ar.subject_type = 'CURRICULUM' THEN c.code
                        WHEN ar.subject_type = 'ACADEMIC_POLICY' THEN ap.code
                    END as subject_code"
                ),
                DB::raw(
                    "CASE
                        WHEN ar.subject_type = 'CURRICULUM' THEN c.version
                        WHEN ar.subject_type = 'ACADEMIC_POLICY' THEN ap.version
                    END as subject_version"
                ),
                'aw.name as workflow_name',
                'aw.applies_to as workflow_applies_to',
                'submitter.name as submitted_by_name',
            ]);

        $stageHistory = DB::table('approval_request_stages as ars')
            ->join('roles as r', 'r.id', '=', 'ars.approver_role_id')
            ->leftJoin(
                'users as u',
                'u.id',
                '=',
                'ars.decided_by'
            )
            ->whereIn(
                'ars.approval_request_id',
                $history->pluck('id')
            )
            ->orderBy('ars.sequence_no')
            ->get([
                'ars.approval_request_id',
                'ars.sequence_no',
                'ars.name',
                'ars.status',
                'ars.remarks',
                'ars.decided_at',
                'r.name as approver_role_name',
                'u.name as decided_by_name',
            ])
            ->groupBy('approval_request_id');

        $history = $history->map(function ($row) use ($stageHistory) {
            $row->stages = $stageHistory
                ->get($row->id, collect())
                ->values();

            return $row;
        });

        return Inertia::render(
            'admin/academic-approval/inbox',
            [
                'pending' => $pending,
                'history' => $history,
                'permissions' => [
                    'decide' =>
                        $request->user()->hasPermission(
                            'approval_request.decide'
                        ),
                ],
            ]
        );
    }

    public function decide(
        DecideApprovalRequest $request,
        ApprovalRequest $approvalRequest,
        ApprovalRequestService $curriculumApprovalService,
        AcademicPolicyApprovalService $academicPolicyApprovalService
    ) {
        if ($approvalRequest->subject_type === 'ACADEMIC_POLICY') {
            $academicPolicyApprovalService->decide(
                $approvalRequest->id,
                $request->user()->id,
                $request->validated('decision'),
                $request->validated('remarks')
            );
        } elseif ($approvalRequest->subject_type === 'CURRICULUM') {
            $curriculumApprovalService->decide(
                $approvalRequest,
                $request->validated('decision'),
                $request->validated('remarks'),
                $request->user()->id
            );
        } else {
            abort(422, 'Unsupported academic approval subject type.');
        }

        return back()->with(
            'success',
            'Approval decision recorded successfully.'
        );
    }
}
