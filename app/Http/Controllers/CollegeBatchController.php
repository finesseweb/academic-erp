<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollegeBatchRequest;
use App\Http\Requests\UpdateCollegeBatchRequest;
use App\Models\Batch;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Services\CollegeBatchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeBatchController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        $this->authorizeCollege($request, $college, 'college_batch.view');

        $batches = Batch::query()
            ->withCount([
                'sections as active_sections_count' => fn ($query) => $query->where('status', 'ACTIVE'),
            ])
            ->with([
                'offering:id,college_id,program_template_id,curriculum_id,academic_session_id,status',
                'offering.programTemplate:id,name,code',
                'offering.curriculum:id,name,code,version',
                'offering.academicSession:id,name,code,status',
                'offering.intake:id,college_program_offering_id,approved_capacity,status',
            ])
            ->whereHas('offering', fn ($query) => $query->where('college_id', $college->id))
            ->orderByDesc('id')
            ->get();

        $offerings = CollegeProgramOffering::query()
            ->with([
                'programTemplate:id,name,code',
                'curriculum:id,name,code,version',
                'academicSession:id,name,code,status',
                'intake:id,college_program_offering_id,approved_capacity,status',
            ])
            ->where('college_id', $college->id)
            ->orderByDesc('academic_session_id')
            ->orderBy('program_template_id')
            ->get()
            ->map(fn (CollegeProgramOffering $offering) => [
                'id' => $offering->id,
                'status' => $offering->status,
                'program_template' => $offering->programTemplate,
                'curriculum' => $offering->curriculum,
                'academic_session' => $offering->academicSession,
                'intake' => $offering->intake,
                'activation_ready' => $offering->status === 'ACTIVE' && $offering->intake?->status === 'ACTIVE',
            ]);

        return Inertia::render('college-batches/index', [
            'college' => $college->only(['id', 'name', 'code', 'status']),
            'batches' => $batches,
            'offerings' => $offerings,
            'summary' => [
                'total' => $batches->count(),
                'active' => $batches->where('status', 'ACTIVE')->count(),
                'inactive' => $batches->where('status', 'INACTIVE')->count(),
                'ready_offerings' => $offerings->where('activation_ready', true)->count(),
            ],
            'can' => [
                'create' => $request->user()->hasCollegePermission('college_batch.create', $college->id),
                'update' => $request->user()->hasCollegePermission('college_batch.update', $college->id),
                'enable' => $request->user()->hasCollegePermission('college_batch.enable', $college->id),
                'disable' => $request->user()->hasCollegePermission('college_batch.disable', $college->id),
            ],
        ]);
    }

    public function store(
        StoreCollegeBatchRequest $request,
        College $college,
        CollegeBatchService $service
    ): RedirectResponse {
        $service->create($college, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Batch created. Activate it after checking the Program Offering and Intake context.',
        ]);
    }

    public function update(
        UpdateCollegeBatchRequest $request,
        College $college,
        Batch $batch,
        CollegeBatchService $service
    ): RedirectResponse {
        $service->update($batch, $college, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Batch updated.',
        ]);
    }

    public function status(
        Request $request,
        College $college,
        Batch $batch,
        CollegeBatchService $service
    ): RedirectResponse {
        $status = $request->validate([
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ])['status'];

        $permission = $status === 'ACTIVE'
            ? 'college_batch.enable'
            : 'college_batch.disable';

        $this->authorizeCollege($request, $college, $permission);
        $service->changeStatus($batch, $college, $status, $request->user()->id, $request->ip());

        return back()->with('toast', [
            'type' => 'success',
            'message' => $status === 'ACTIVE'
                ? 'Batch activated.'
                : 'Batch deactivated.',
        ]);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
