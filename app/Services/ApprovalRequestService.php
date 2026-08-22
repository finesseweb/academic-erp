<?php

namespace App\Services;

use App\Models\ApprovalRequest;
use App\Models\ApprovalRequestStage;
use App\Models\ApprovalWorkflow;
use App\Models\Curriculum;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalRequestService
{
    public function __construct(
        private readonly CurriculumStructureValidationService $validationService
    ) {}

    public function submitCurriculum(
        Curriculum $curriculum,
        ApprovalWorkflow $workflow,
        int $actorId
    ): ApprovalRequest {
        if ($curriculum->lifecycle_status !== 'DRAFT') {
            throw ValidationException::withMessages([
                'curriculum' =>
                    'Only a DRAFT Curriculum can be submitted for approval.',
            ]);
        }

        if (! in_array(
            $curriculum->approval_status ?? 'NOT_SUBMITTED',
            ['NOT_SUBMITTED', 'RETURNED', 'REJECTED'],
            true
        )) {
            throw ValidationException::withMessages([
                'curriculum' =>
                    'This Curriculum is already under approval or has already been approved.',
            ]);
        }

        if (
            (int) $workflow->university_id !==
                (int) $curriculum->university_id ||
            $workflow->applies_to !== 'CURRICULUM' ||
            $workflow->status !== 'ACTIVE'
        ) {
            throw ValidationException::withMessages([
                'approval_workflow_id' =>
                    'Select an active Curriculum approval workflow from this University.',
            ]);
        }

        $workflow->load([
            'stages' => fn ($query) =>
                $query->where('status', 'ACTIVE')
                    ->orderBy('sequence_no'),
        ]);

        if ($workflow->stages->isEmpty()) {
            throw ValidationException::withMessages([
                'approval_workflow_id' =>
                    'The selected workflow has no active approval levels.',
            ]);
        }

        $validation = $this->validationService
            ->validate($curriculum);

        if (! $validation['valid']) {
            throw ValidationException::withMessages([
                'curriculum' =>
                    'Validate Structure must pass before the Curriculum can be submitted for approval.',
            ]);
        }

        $activeRequest = ApprovalRequest::query()
            ->where('subject_type', 'CURRICULUM')
            ->where('subject_id', $curriculum->id)
            ->where('status', 'PENDING')
            ->exists();

        if ($activeRequest) {
            throw ValidationException::withMessages([
                'curriculum' =>
                    'This Curriculum already has a pending approval request.',
            ]);
        }

        return DB::transaction(function () use (
            $curriculum,
            $workflow,
            $actorId
        ) {
            $firstSequence = (int) $workflow->stages
                ->first()->sequence_no;

            $request = ApprovalRequest::create([
                'approval_workflow_id' => $workflow->id,
                'university_id' => $curriculum->university_id,
                'subject_type' => 'CURRICULUM',
                'subject_id' => $curriculum->id,
                'status' => 'PENDING',
                'current_stage_sequence' => $firstSequence,
                'submitted_by' => $actorId,
                'submitted_at' => now(),
            ]);

            foreach ($workflow->stages as $stage) {
                ApprovalRequestStage::create([
                    'approval_request_id' => $request->id,
                    'sequence_no' => $stage->sequence_no,
                    'name' => $stage->name,
                    'approver_role_id' => $stage->approver_role_id,
                    'status' =>
                        (int) $stage->sequence_no === $firstSequence
                            ? 'PENDING'
                            : 'WAITING',
                    'remarks_required_on_reject' =>
                        $stage->remarks_required_on_reject,
                    'remarks_required_on_return' =>
                        $stage->remarks_required_on_return,
                ]);
            }

            $before = $curriculum->toArray();

            $curriculum->update([
                'approval_status' => 'SUBMITTED',
                'updated_by' => $actorId,
            ]);

            $this->audit(
                'CURRICULUM_SUBMITTED_FOR_APPROVAL',
                'curriculum',
                $curriculum->id,
                $before,
                $curriculum->fresh()->toArray(),
                $actorId
            );

            return $request->fresh('stages');
        });
    }

    public function decide(
        ApprovalRequest $request,
        string $decision,
        ?string $remarks,
        int $actorId
    ): ApprovalRequest {
        if ($request->status !== 'PENDING') {
            throw ValidationException::withMessages([
                'request' => 'This approval request is already complete.',
            ]);
        }

        $request->load('stages');

        $currentStage = $request->stages
            ->firstWhere(
                'sequence_no',
                $request->current_stage_sequence
            );

        if (! $currentStage || $currentStage->status !== 'PENDING') {
            throw ValidationException::withMessages([
                'request' =>
                    'The current approval level could not be resolved.',
            ]);
        }

        $this->assertActorHasApproverRole(
            $actorId,
            (int) $currentStage->approver_role_id
        );

        $remarks = trim((string) $remarks);

        if (
            $decision === 'REJECT' &&
            $currentStage->remarks_required_on_reject &&
            $remarks === ''
        ) {
            throw ValidationException::withMessages([
                'remarks' => 'Remarks are required when rejecting.',
            ]);
        }

        if (
            $decision === 'RETURN' &&
            $currentStage->remarks_required_on_return &&
            $remarks === ''
        ) {
            throw ValidationException::withMessages([
                'remarks' =>
                    'Remarks are required when returning for correction.',
            ]);
        }

        $curriculum = Curriculum::query()
            ->findOrFail($request->subject_id);

        return DB::transaction(function () use (
            $request,
            $currentStage,
            $curriculum,
            $decision,
            $remarks,
            $actorId
        ) {
            if ($decision === 'APPROVE') {
                $currentStage->update([
                    'status' => 'APPROVED',
                    'decided_by' => $actorId,
                    'remarks' => $remarks ?: null,
                    'decided_at' => now(),
                ]);

                $nextStage = $request->stages
                    ->where(
                        'sequence_no',
                        '>',
                        $currentStage->sequence_no
                    )
                    ->sortBy('sequence_no')
                    ->first();

                if ($nextStage) {
                    $nextStage->update([
                        'status' => 'PENDING',
                    ]);

                    $request->update([
                        'current_stage_sequence' =>
                            $nextStage->sequence_no,
                    ]);

                    $curriculum->update([
                        'approval_status' => 'UNDER_APPROVAL',
                        'updated_by' => $actorId,
                    ]);

                    $this->audit(
                        'CURRICULUM_APPROVAL_LEVEL_APPROVED',
                        'approval_request',
                        $request->id,
                        null,
                        [
                            'stage_sequence' =>
                                $currentStage->sequence_no,
                            'next_stage_sequence' =>
                                $nextStage->sequence_no,
                        ],
                        $actorId
                    );

                    return $request->fresh('stages');
                }

                $request->update([
                    'status' => 'APPROVED',
                    'current_stage_sequence' => null,
                    'completed_at' => now(),
                ]);

                $before = $curriculum->toArray();

                $curriculum->update([
                    'approval_status' => 'APPROVED',
                    'lifecycle_status' => 'ACTIVE',
                    'updated_by' => $actorId,
                ]);

                $this->audit(
                    'CURRICULUM_APPROVED_AND_ACTIVATED',
                    'curriculum',
                    $curriculum->id,
                    $before,
                    $curriculum->fresh()->toArray(),
                    $actorId
                );

                return $request->fresh('stages');
            }

            $stageStatus =
                $decision === 'REJECT'
                    ? 'REJECTED'
                    : 'RETURNED';

            $currentStage->update([
                'status' => $stageStatus,
                'decided_by' => $actorId,
                'remarks' => $remarks ?: null,
                'decided_at' => now(),
            ]);

            $request->update([
                'status' => $stageStatus,
                'current_stage_sequence' => null,
                'completed_at' => now(),
            ]);

            $before = $curriculum->toArray();

            $curriculum->update([
                'approval_status' => $stageStatus,
                'updated_by' => $actorId,
            ]);

            $this->audit(
                $decision === 'REJECT'
                    ? 'CURRICULUM_APPROVAL_REJECTED'
                    : 'CURRICULUM_APPROVAL_RETURNED',
                'curriculum',
                $curriculum->id,
                $before,
                $curriculum->fresh()->toArray(),
                $actorId
            );

            return $request->fresh('stages');
        });
    }

    private function assertActorHasApproverRole(
        int $actorId,
        int $roleId
    ): void {
        $now = now();

        $hasRole = DB::table('user_roles')
            ->where('user_id', $actorId)
            ->where('role_id', $roleId)
            ->where('status', 'ACTIVE')
            ->where(function ($query) use ($now) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('effective_until')
                    ->orWhere('effective_until', '>=', $now);
            })
            ->exists();

        if (! $hasRole) {
            abort(403);
        }
    }

    private function audit(
        string $event,
        string $type,
        int $id,
        ?array $before,
        ?array $after,
        int $actorId
    ): void {
        DB::table('audit_logs')->insert([
            'event' => $event,
            'resource_type' => $type,
            'resource_id' => $id,
            'before' => $before
                ? json_encode($before)
                : null,
            'after' => $after
                ? json_encode($after)
                : null,
            'actor_user_id' => $actorId,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
