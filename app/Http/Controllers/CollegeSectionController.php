<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollegeSectionRequest;
use App\Http\Requests\UpdateCollegeSectionRequest;
use App\Models\Batch;
use App\Models\College;
use App\Models\Section;
use App\Services\CollegeSectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeSectionController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        $this->authorizeCollege($request, $college, 'college_section.view');

        $sections = Section::query()
            ->with([
                'batch:id,college_program_offering_id,code,name,status',
                'batch.offering:id,college_id,program_template_id,curriculum_id,academic_session_id,status',
                'batch.offering.programTemplate:id,name,code',
                'batch.offering.curriculum:id,name,code,version',
                'batch.offering.academicSession:id,name,code,status',
                'batch.offering.intake:id,college_program_offering_id,approved_capacity,status',
            ])
            ->whereHas('batch.offering', fn ($q) => $q->where('college_id', $college->id))
            ->orderByDesc('id')
            ->get();

        $batches = Batch::query()
            ->with([
                'offering:id,college_id,program_template_id,curriculum_id,academic_session_id,status',
                'offering.programTemplate:id,name,code',
                'offering.curriculum:id,name,code,version',
                'offering.academicSession:id,name,code,status',
                'offering.intake:id,college_program_offering_id,approved_capacity,status',
            ])
            ->whereHas('offering', fn ($q) => $q->where('college_id', $college->id))
            ->orderByDesc('id')
            ->get()
            ->map(fn (Batch $batch) => [
                'id' => $batch->id,
                'code' => $batch->code,
                'name' => $batch->name,
                'status' => $batch->status,
                'offering' => $batch->offering,
                'activation_ready' => $batch->status === 'ACTIVE'
                    && $batch->offering?->status === 'ACTIVE'
                    && $batch->offering?->intake?->status === 'ACTIVE',
            ]);

        return Inertia::render('college-sections/index', [
            'college' => $college->only(['id', 'name', 'code', 'status']),
            'sections' => $sections,
            'batches' => $batches,
            'summary' => [
                'total' => $sections->count(),
                'active' => $sections->where('status', 'ACTIVE')->count(),
                'inactive' => $sections->where('status', 'INACTIVE')->count(),
                'active_batches' => $batches->where('activation_ready', true)->count(),
            ],
            'can' => [
                'create' => $request->user()->hasCollegePermission('college_section.create', $college->id),
                'update' => $request->user()->hasCollegePermission('college_section.update', $college->id),
                'enable' => $request->user()->hasCollegePermission('college_section.enable', $college->id),
                'disable' => $request->user()->hasCollegePermission('college_section.disable', $college->id),
            ],
        ]);
    }

    public function store(StoreCollegeSectionRequest $request, College $college, CollegeSectionService $service): RedirectResponse
    {
        $service->create($college, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Section created. Activate it after confirming the parent Batch context.']);
    }

    public function update(UpdateCollegeSectionRequest $request, College $college, Section $section, CollegeSectionService $service): RedirectResponse
    {
        $service->update($section, $college, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Section updated.']);
    }

    public function status(Request $request, College $college, Section $section, CollegeSectionService $service): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]])['status'];
        $permission = $status === 'ACTIVE' ? 'college_section.enable' : 'college_section.disable';
        $this->authorizeCollege($request, $college, $permission);

        $service->changeStatus($section, $college, $status, $request->user()->id, $request->ip());

        return back()->with('toast', [
            'type' => 'success',
            'message' => $status === 'ACTIVE' ? 'Section activated.' : 'Section deactivated.',
        ]);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
