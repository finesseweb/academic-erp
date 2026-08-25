<?php

namespace App\Services;

use App\Models\AcademicPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AcademicPolicyApprovalService
{
    public function __construct(
        private readonly AcademicPolicyValidationService $validationService
    ) {}

    public function submit(
        AcademicPolicy $policy,
        int $workflowId,
        int $actorId
    ): int {
        if ($policy->lifecycle_status !== 'DRAFT') {
            throw ValidationException::withMessages([
                'academic_policy' => 'Only a Draft Academic Policy can be submitted.',
            ]);
        }

        if (! in_array(
            $policy->approval_status ?? 'NOT_SUBMITTED',
            ['NOT_SUBMITTED', 'RETURNED', 'REJECTED'],
            true
        )) {
            throw ValidationException::withMessages([
                'academic_policy' => 'This Academic Policy is already in approval or approved.',
            ]);
        }

        if (! $this->validationService->hasCurrentValidCheckpoint($policy)) {
            throw ValidationException::withMessages([
                'academic_policy' => 'Validate the complete Academic Policy before submission.',
            ]);
        }

        $live = $this->validationService->validate($policy);
        if (! $live['valid']) {
            throw ValidationException::withMessages([
                'academic_policy' => implode(' ', $live['errors']),
            ]);
        }

        $workflow = DB::table('approval_workflows')
            ->where('id', $workflowId)
            ->where('university_id', $policy->university_id)
            ->where('applies_to', 'ACADEMIC_POLICY')
            ->where('status', 'ACTIVE')
            ->first();

        if (! $workflow) {
            throw ValidationException::withMessages([
                'approval_workflow_id' => 'Select an active Academic Policy approval workflow from this University.',
            ]);
        }

        $stages = DB::table('approval_workflow_stages')
            ->where('approval_workflow_id', $workflowId)
            ->where('status', 'ACTIVE')
            ->orderBy('sequence_no')
            ->get();

        if ($stages->isEmpty()) {
            throw ValidationException::withMessages([
                'approval_workflow_id' => 'The selected workflow has no active approval stages.',
            ]);
        }

        $alreadyPending = DB::table('approval_requests')
            ->where('subject_type', 'ACADEMIC_POLICY')
            ->where('subject_id', $policy->id)
            ->where('status', 'PENDING')
            ->exists();

        if ($alreadyPending) {
            throw ValidationException::withMessages([
                'academic_policy' => 'A pending approval request already exists for this policy.',
            ]);
        }

        return DB::transaction(function () use (
            $policy,
            $workflowId,
            $actorId,
            $stages
        ) {
            $firstSequence = (int) $stages->first()->sequence_no;

            $requestId = DB::table('approval_requests')->insertGetId([
                'approval_workflow_id' => $workflowId,
                'university_id' => $policy->university_id,
                'subject_type' => 'ACADEMIC_POLICY',
                'subject_id' => $policy->id,
                'status' => 'PENDING',
                'current_stage_sequence' => $firstSequence,
                'submitted_by' => $actorId,
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($stages as $stage) {
                DB::table('approval_request_stages')->insert([
                    'approval_request_id' => $requestId,
                    'sequence_no' => $stage->sequence_no,
                    'name' => $stage->name,
                    'approver_role_id' => $stage->approver_role_id,
                    'status' => (int) $stage->sequence_no === $firstSequence
                        ? 'PENDING'
                        : 'WAITING',
                    'remarks_required_on_reject' => $stage->remarks_required_on_reject,
                    'remarks_required_on_return' => $stage->remarks_required_on_return,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $policy->forceFill([
                'approval_status' => 'UNDER_APPROVAL',
                'updated_by' => $actorId,
            ])->save();

            $this->audit(
                'ACADEMIC_POLICY_SUBMITTED_FOR_APPROVAL',
                $policy,
                ['approval_request_id' => $requestId, 'workflow_id' => $workflowId],
                $actorId
            );

            return $requestId;
        });
    }

    public function decide(
        int $approvalRequestId,
        int $actorId,
        string $decision,
        ?string $remarks
    ): void {
        $decision = strtoupper($decision);

        if (! in_array($decision, ['APPROVE', 'RETURN', 'REJECT'], true)) {
            throw ValidationException::withMessages([
                'decision' => 'Decision must be Approve, Return or Reject.',
            ]);
        }

        DB::transaction(function () use (
            $approvalRequestId,
            $actorId,
            $decision,
            $remarks
        ) {
            $request = DB::table('approval_requests')
                ->lockForUpdate()
                ->where('id', $approvalRequestId)
                ->where('subject_type', 'ACADEMIC_POLICY')
                ->first();

            if (! $request || $request->status !== 'PENDING') {
                throw ValidationException::withMessages([
                    'approval_request' => 'This approval request is no longer pending.',
                ]);
            }

            $stage = DB::table('approval_request_stages')
                ->lockForUpdate()
                ->where('approval_request_id', $request->id)
                ->where('sequence_no', $request->current_stage_sequence)
                ->where('status', 'PENDING')
                ->first();

            if (! $stage) {
                throw ValidationException::withMessages([
                    'approval_request' => 'Current pending approval stage was not found.',
                ]);
            }

            if (! $this->actorHasRole($actorId, (int) $stage->approver_role_id)) {
                abort(403, 'You are not assigned the approver role for this stage.');
            }

            if (
                $decision === 'REJECT'
                && $stage->remarks_required_on_reject
                && trim((string) $remarks) === ''
            ) {
                throw ValidationException::withMessages([
                    'remarks' => 'Remarks are required when rejecting this request.',
                ]);
            }

            if (
                $decision === 'RETURN'
                && $stage->remarks_required_on_return
                && trim((string) $remarks) === ''
            ) {
                throw ValidationException::withMessages([
                    'remarks' => 'Remarks are required when returning this request.',
                ]);
            }

            $stageStatus = match ($decision) {
                'APPROVE' => 'APPROVED',
                'RETURN' => 'RETURNED',
                'REJECT' => 'REJECTED',
            };

            DB::table('approval_request_stages')
                ->where('id', $stage->id)
                ->update([
                    'status' => $stageStatus,
                    'decided_by' => $actorId,
                    'remarks' => $remarks,
                    'decided_at' => now(),
                    'updated_at' => now(),
                ]);

            $policy = AcademicPolicy::query()
                ->lockForUpdate()
                ->findOrFail($request->subject_id);

            if ($decision === 'RETURN' || $decision === 'REJECT') {
                $finalStatus = $decision === 'RETURN' ? 'RETURNED' : 'REJECTED';

                DB::table('approval_requests')
                    ->where('id', $request->id)
                    ->update([
                        'status' => $finalStatus,
                        'completed_at' => now(),
                        'updated_at' => now(),
                    ]);

                $policy->forceFill([
                    'lifecycle_status' => 'DRAFT',
                    'approval_status' => $finalStatus,
                    'is_current_version' => false,
                    'updated_by' => $actorId,
                ])->save();

                $this->audit(
                    'ACADEMIC_POLICY_APPROVAL_'.$finalStatus,
                    $policy,
                    ['approval_request_id' => $request->id, 'remarks' => $remarks],
                    $actorId
                );

                return;
            }

            $next = DB::table('approval_request_stages')
                ->where('approval_request_id', $request->id)
                ->where('sequence_no', '>', $stage->sequence_no)
                ->where('status', 'WAITING')
                ->orderBy('sequence_no')
                ->first();

            if ($next) {
                DB::table('approval_request_stages')
                    ->where('id', $next->id)
                    ->update([
                        'status' => 'PENDING',
                        'updated_at' => now(),
                    ]);

                DB::table('approval_requests')
                    ->where('id', $request->id)
                    ->update([
                        'current_stage_sequence' => $next->sequence_no,
                        'updated_at' => now(),
                    ]);

                $policy->forceFill([
                    'approval_status' => 'UNDER_APPROVAL',
                    'updated_by' => $actorId,
                ])->save();

                return;
            }

            DB::table('approval_requests')
                ->where('id', $request->id)
                ->update([
                    'status' => 'APPROVED',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            $policy->forceFill([
                'lifecycle_status' => 'ACTIVE',
                'approval_status' => 'APPROVED',
                'is_current_version' => true,
                'updated_by' => $actorId,
            ])->save();

            if ($policy->parent_policy_id) {
                AcademicPolicy::query()
                    ->whereKey($policy->parent_policy_id)
                    ->update([
                        'is_current_version' => false,
                        'superseded_by_id' => $policy->id,
                        'updated_at' => now(),
                    ]);
            }

            $this->audit(
                'ACADEMIC_POLICY_APPROVED_ACTIVE',
                $policy,
                ['approval_request_id' => $request->id],
                $actorId
            );
        });
    }

    private function actorHasRole(int $actorId, int $roleId): bool
    {
        return DB::table('user_roles')
            ->where('user_id', $actorId)
            ->where('role_id', $roleId)
            ->where('status', 'ACTIVE')
            ->where(function ($query) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', now());
            })
            ->exists();
    }

    private function audit(
        string $event,
        AcademicPolicy $policy,
        array $after,
        int $actorId
    ): void {
        DB::table('audit_logs')->insert([
            'event' => $event,
            'resource_type' => 'academic_policy',
            'resource_id' => $policy->id,
            'before' => null,
            'after' => json_encode($after),
            'actor_user_id' => $actorId,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
