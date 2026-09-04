<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollegeProgramOfferingRequest;
use App\Http\Requests\UpdateCollegeProgramOfferingRequest;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Services\CollegeProgramOfferingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeProgramOfferingController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        $this->authorizeCollege($request, $college, 'college_program_offering.view');

        $offerings = CollegeProgramOffering::query()
            ->with([
                'programTemplate:id,name,code',
                'curriculum:id,name,code,version',
                'academicSession:id,name,code,starts_on,ends_on,status',
            ])
            ->where('college_id', $college->id)
            ->orderByDesc('academic_session_id')
            ->orderBy('program_template_id')
            ->get();

        $activeBatchCounts = DB::table('batches')
            ->selectRaw('college_program_offering_id, COUNT(*) as total')
            ->where('status', 'ACTIVE')
            ->whereIn('college_program_offering_id', $offerings->pluck('id'))
            ->groupBy('college_program_offering_id')
            ->pluck('total', 'college_program_offering_id');

        $offerings->each(function ($offering) use ($activeBatchCounts) {
            $offering->setAttribute(
                'active_batch_count',
                (int) ($activeBatchCounts[$offering->id] ?? 0)
            );
        });

        $programTemplates = DB::table('program_templates')
            ->where('university_id', $college->university_id)
            ->where('status', 'ACTIVE')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $academicSessions = DB::table('academic_sessions')
            ->where('university_id', $college->university_id)
            ->whereIn('status', ['PLANNED', 'ACTIVE'])
            ->orderByDesc('is_current')
            ->orderByDesc('starts_on')
            ->get([
                'id',
                'name',
                'code',
                'status',
                'is_current',
            ]);

        /*
         * Program Offerings may adopt only the CURRENT approved Curriculum
         * version for a Program + Academic Session.
         *
         * Curriculum currentness is intentionally DERIVED by the existing
         * amendment/version-chain contract:
         * current = ACTIVE + APPROVED and has no direct APPROVED amendment.
         * Do not introduce a second mutable is_current flag for curricula.
         */
        $curricula = DB::table('curricula as c')
            ->where('c.university_id', $college->university_id)
            ->where('c.lifecycle_status', 'ACTIVE')
            ->where('c.approval_status', 'APPROVED')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('curricula as child')
                    ->whereColumn(
                        'child.parent_curriculum_id',
                        'c.id'
                    )
                    ->where('child.approval_status', 'APPROVED');
            })
            ->orderBy('c.name')
            ->get([
                'c.id',
                'c.program_template_id',
                'c.academic_session_id',
                'c.name',
                'c.code',
                'c.version',
            ]);

        return Inertia::render('college-program-offerings/index', [
            'college' => $college->only(['id', 'name', 'code', 'status']),
            'offerings' => $offerings,
            'programTemplates' => $programTemplates,
            'academicSessions' => $academicSessions,
            'curricula' => $curricula,
            'can' => [
                'create' => $request->user()->hasCollegePermission('college_program_offering.create', $college->id),
                'update' => $request->user()->hasCollegePermission('college_program_offering.update', $college->id),
                'enable' => $request->user()->hasCollegePermission('college_program_offering.enable', $college->id),
                'disable' => $request->user()->hasCollegePermission('college_program_offering.disable', $college->id),
            ],
        ]);
    }

    public function store(
        StoreCollegeProgramOfferingRequest $request,
        College $college,
        CollegeProgramOfferingService $service
    ): RedirectResponse {
        $service->create($college, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Program offering created. Activate it when ready for use.',
        ]);
    }

    public function update(
        UpdateCollegeProgramOfferingRequest $request,
        College $college,
        CollegeProgramOffering $offering,
        CollegeProgramOfferingService $service
    ): RedirectResponse {
        $service->update($offering, $college, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Program offering updated.',
        ]);
    }

    public function status(
        Request $request,
        College $college,
        CollegeProgramOffering $offering,
        CollegeProgramOfferingService $service
    ): RedirectResponse {
        abort_unless((int) $offering->college_id === (int) $college->id, 404);

        $status = $request->validate([
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ])['status'];

        $permission = $status === 'ACTIVE'
            ? 'college_program_offering.enable'
            : 'college_program_offering.disable';

        $this->authorizeCollege($request, $college, $permission);
        $service->changeStatus($offering, $college, $status, $request->user()->id, $request->ip());

        return back()->with('toast', [
            'type' => 'success',
            'message' => $status === 'ACTIVE'
                ? 'Program offering activated.'
                : 'Program offering deactivated.',
        ]);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
