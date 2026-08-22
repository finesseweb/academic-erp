<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreApprovalWorkflowRequest;
use App\Http\Requests\StoreApprovalWorkflowStageRequest;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStage;
use App\Models\University;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ApprovalWorkflowController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('approval_workflow.view'), 403);

        $university = University::query()->firstOrFail();

        $workflows = ApprovalWorkflow::query()
            ->with(['stages.approverRole:id,name,code'])
            ->where('university_id', $university->id)
            ->orderBy('name')
            ->get();

        $roles = DB::table('roles')
            ->where('status', 'ACTIVE')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return Inertia::render('admin/academic-approval/workflows', [
            'workflows' => $workflows,
            'roles' => $roles,
            'permissions' => [
                'create' => $request->user()->hasPermission('approval_workflow.create'),
                'update' => $request->user()->hasPermission('approval_workflow.update'),
                'disable' => $request->user()->hasPermission('approval_workflow.disable'),
            ],
        ]);
    }

    public function store(StoreApprovalWorkflowRequest $request): RedirectResponse
    {
        $university = University::query()->firstOrFail();
        $data = $request->validated();

        if (ApprovalWorkflow::query()
            ->where('university_id', $university->id)
            ->where('code', strtoupper($data['code']))
            ->exists()) {
            throw ValidationException::withMessages(['code' => 'Workflow code already exists.']);
        }

        $workflow = ApprovalWorkflow::create([
            'university_id' => $university->id,
            'name' => trim($data['name']),
            'code' => strtoupper(trim($data['code'])),
            'applies_to' => $data['applies_to'],
            'description' => $data['description'] ?? null,
            'status' => 'ACTIVE',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->audit('APPROVAL_WORKFLOW_CREATED', 'approval_workflow', $workflow->id, $request->user()->id);

        return back()->with('success', 'Approval workflow created successfully.');
    }

    public function storeStage(
        StoreApprovalWorkflowStageRequest $request,
        ApprovalWorkflow $workflow
    ): RedirectResponse {
        $university = University::query()->firstOrFail();
        abort_unless((int) $workflow->university_id === (int) $university->id, 404);

        $data = $request->validated();

        if ($workflow->stages()->where('sequence_no', $data['sequence_no'])->exists()) {
            throw ValidationException::withMessages([
                'sequence_no' => 'This approval level already exists in the workflow.',
            ]);
        }

        $stage = $workflow->stages()->create([
            ...$data,
            'status' => 'ACTIVE',
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $this->audit('APPROVAL_WORKFLOW_STAGE_CREATED', 'approval_workflow_stage', $stage->id, $request->user()->id);

        return back()->with('success', 'Approval level added successfully.');
    }

    public function status(Request $request, ApprovalWorkflow $workflow): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('approval_workflow.disable'), 403);

        $university = University::query()->firstOrFail();
        abort_unless((int) $workflow->university_id === (int) $university->id, 404);

        $workflow->update([
            'status' => $workflow->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE',
            'updated_by' => $request->user()->id,
        ]);

        $this->audit('APPROVAL_WORKFLOW_STATUS_CHANGED', 'approval_workflow', $workflow->id, $request->user()->id);

        return back()->with('success', 'Workflow status updated.');
    }

    private function audit(string $event, string $type, int $id, int $actorId): void
    {
        DB::table('audit_logs')->insert([
            'event' => $event,
            'resource_type' => $type,
            'resource_id' => $id,
            'before' => null,
            'after' => null,
            'actor_user_id' => $actorId,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
