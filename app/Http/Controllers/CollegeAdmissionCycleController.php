<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollegeAdmissionCycleRequest;
use App\Http\Requests\UpdateCollegeAdmissionCycleRequest;
use App\Models\College;
use App\Models\CollegeAdmissionCycle;
use App\Models\CollegeProgramOffering;
use App\Services\CollegeAdmissionCycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeAdmissionCycleController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        $this->authorizeCollege($request, $college, 'college_admission_cycle.view');

        $offerings = CollegeProgramOffering::query()
            ->with([
                'programTemplate:id,name,code',
                'academicSession:id,name,code,starts_on,ends_on,status,is_current',
                'curriculum:id,name,code,version',
                'intake:id,college_program_offering_id,status,approved_capacity',
            ])
            ->where('college_id', $college->id)
            ->where('status', 'ACTIVE')
            ->whereHas('academicSession', fn ($q) => $q->whereIn('status', ['PLANNED', 'ACTIVE']))
            ->get()
            ->sortBy([
                fn ($a, $b) => (int) $b->academicSession->is_current <=> (int) $a->academicSession->is_current,
                fn ($a, $b) => strcmp($a->programTemplate->name, $b->programTemplate->name),
            ])
            ->values();

        $cycles = CollegeAdmissionCycle::query()
            ->with([
                'programOffering.programTemplate:id,name,code',
                'programOffering.academicSession:id,name,code,is_current',
                'programOffering.curriculum:id,name,code,version',
                'academicSession:id,name,code,is_current',
            ])
            ->where('college_id', $college->id)
            ->orderByRaw("FIELD(status, 'ACTIVE', 'INACTIVE', 'CLOSED')")
            ->orderByDesc('id')
            ->get();

        return Inertia::render('college-admission-cycles/index', [
            'college' => $college->only(['id', 'name', 'code', 'status']),
            'offerings' => $offerings,
            'cycles' => $cycles,
            'can' => [
                'create' => $request->user()->hasCollegePermission('college_admission_cycle.create', $college->id),
                'update' => $request->user()->hasCollegePermission('college_admission_cycle.update', $college->id),
                'enable' => $request->user()->hasCollegePermission('college_admission_cycle.enable', $college->id)
                    || $request->user()->hasCollegePermission('college_admission_cycle.update', $college->id),
                'disable' => $request->user()->hasCollegePermission('college_admission_cycle.disable', $college->id)
                    || $request->user()->hasCollegePermission('college_admission_cycle.update', $college->id),
            ],
        ]);
    }

    public function store(StoreCollegeAdmissionCycleRequest $request, College $college, CollegeAdmissionCycleService $service): RedirectResponse
    {
        $service->create($college, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Admission Cycle created as INACTIVE.']);
    }

    public function update(UpdateCollegeAdmissionCycleRequest $request, College $college, CollegeAdmissionCycle $cycle, CollegeAdmissionCycleService $service): RedirectResponse
    {
        $service->update($cycle, $college, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Admission Cycle updated.']);
    }

    public function status(Request $request, College $college, CollegeAdmissionCycle $cycle, CollegeAdmissionCycleService $service): RedirectResponse
    {
        $status = (string) $request->validate([
            'status' => ['required', Rule::in(['INACTIVE', 'ACTIVE', 'CLOSED'])],
        ])['status'];

        $permission = $status === 'ACTIVE' ? 'college_admission_cycle.enable' : 'college_admission_cycle.disable';
        $hasLifecyclePermission = $request->user()->hasCollegePermission($permission, $college->id)
            || $request->user()->hasCollegePermission('college_admission_cycle.update', $college->id);
        abort_unless($hasLifecyclePermission, 403);

        $service->changeStatus($cycle, $college, $status, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => "Admission Cycle status changed to {$status}."]);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
