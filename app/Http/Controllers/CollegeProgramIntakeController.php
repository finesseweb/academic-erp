<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollegeProgramIntakeAllocationRequest;
use App\Http\Requests\StoreCollegeProgramIntakeRequest;
use App\Http\Requests\UpdateCollegeProgramIntakeAllocationRequest;
use App\Http\Requests\UpdateCollegeProgramIntakeRequest;
use App\Models\College;
use App\Models\CollegeProgramIntake;
use App\Models\CollegeProgramIntakeAllocation;
use App\Models\CollegeProgramOffering;
use App\Services\CollegeProgramIntakeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeProgramIntakeController extends Controller
{
    public function index(
        Request $request,
        College $college
    ): Response {
        $this->authorizeCollege(
            $request,
            $college,
            'college_program_intake.view'
        );

        $intakes = CollegeProgramIntake::query()
            ->with([
                'offering.programTemplate:id,name,code',
                'offering.curriculum:id,name,code,version',
                'offering.academicSession:id,name,code,is_current,status',
                'allocations.discipline:id,name,code,kind,parent_id,status',
                'allocations.specialization:id,name,code,kind,parent_id,status',
            ])
            ->whereHas(
                'offering',
                fn ($query) =>
                    $query->where('college_id', $college->id)
            )
            ->orderByDesc('id')
            ->get();

        $offeringIdsWithIntake = $intakes
            ->pluck('college_program_offering_id');

        $availableOfferings = CollegeProgramOffering::query()
            ->with([
                'programTemplate:id,name,code,display_order',
                'curriculum:id,name,code,version',
                'academicSession:id,name,code,is_current,status,starts_on',
            ])
            ->where('college_id', $college->id)
            ->where('status', 'ACTIVE')
            ->whereNotIn('id', $offeringIdsWithIntake)
            ->get()
            ->sortBy([
                fn ($a, $b) =>
                    (int) $b->academicSession->is_current <=>
                    (int) $a->academicSession->is_current,
                fn ($a, $b) =>
                    ($a->programTemplate->display_order ?? 65535) <=>
                    ($b->programTemplate->display_order ?? 65535),
                fn ($a, $b) =>
                    strcmp(
                        $a->programTemplate->name,
                        $b->programTemplate->name
                    ),
            ])
            ->values();

        $programTemplateIds = $intakes
            ->pluck('offering.program_template_id')
            ->merge(
                $availableOfferings
                    ->pluck('program_template_id')
            )
            ->filter()
            ->unique()
            ->values();

        $disciplineMappings = DB::table(
            'program_template_disciplines as ptd'
        )
            ->join(
                'academic_disciplines as d',
                'd.id',
                '=',
                'ptd.discipline_id'
            )
            ->whereIn(
                'ptd.program_template_id',
                $programTemplateIds
            )
            ->where('d.status', 'ACTIVE')
            ->where('d.kind', 'DISCIPLINE')
            ->orderBy('d.name')
            ->get([
                'ptd.id as mapping_id',
                'ptd.program_template_id',
                'd.id',
                'd.name',
                'd.code',
            ]);

        $mappingIds = $disciplineMappings
            ->pluck('mapping_id');

        $specializationMappings = DB::table(
            'program_template_discipline_specializations as ptds'
        )
            ->join(
                'academic_disciplines as s',
                's.id',
                '=',
                'ptds.specialization_id'
            )
            ->whereIn(
                'ptds.program_template_discipline_id',
                $mappingIds
            )
            ->where('s.status', 'ACTIVE')
            ->where('s.kind', 'SPECIALIZATION')
            ->orderBy('s.name')
            ->get([
                'ptds.program_template_discipline_id as mapping_id',
                's.id',
                's.parent_id',
                's.name',
                's.code',
            ]);

        $disciplines = $disciplineMappings
            ->map(function ($discipline) use (
                $specializationMappings
            ) {
                return [
                    'mapping_id' => $discipline->mapping_id,
                    'program_template_id' =>
                        $discipline->program_template_id,
                    'id' => $discipline->id,
                    'name' => $discipline->name,
                    'code' => $discipline->code,
                    'specializations' =>
                        $specializationMappings
                            ->where(
                                'mapping_id',
                                $discipline->mapping_id
                            )
                            ->values(),
                ];
            })
            ->values();

        return Inertia::render(
            'college-program-intakes/index',
            [
                'college' =>
                    $college->only([
                        'id',
                        'name',
                        'code',
                        'status',
                    ]),
                'intakes' => $intakes,
                'availableOfferings' => $availableOfferings,
                'disciplines' => $disciplines,
                'can' => [
                    'create' =>
                        $request->user()->hasCollegePermission(
                            'college_program_intake.create',
                            $college->id
                        ),
                    'update' =>
                        $request->user()->hasCollegePermission(
                            'college_program_intake.update',
                            $college->id
                        ),
                    'enable' =>
                        $request->user()->hasCollegePermission(
                            'college_program_intake.enable',
                            $college->id
                        ),
                    'disable' =>
                        $request->user()->hasCollegePermission(
                            'college_program_intake.disable',
                            $college->id
                        ),
                ],
            ]
        );
    }

    public function store(
        StoreCollegeProgramIntakeRequest $request,
        College $college,
        CollegeProgramIntakeService $service
    ): RedirectResponse {
        $service->create(
            $college,
            $request->validated(),
            $request->user()->id,
            $request->ip()
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' =>
                'Intake / Seat Capacity created. Configure allocations if required, then activate it.',
        ]);
    }

    public function update(
        UpdateCollegeProgramIntakeRequest $request,
        College $college,
        CollegeProgramIntake $intake,
        CollegeProgramIntakeService $service
    ): RedirectResponse {
        $service->update(
            $intake,
            $college,
            $request->validated(),
            $request->user()->id,
            $request->ip()
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Intake / Seat Capacity updated.',
        ]);
    }

    public function storeAllocation(
        StoreCollegeProgramIntakeAllocationRequest $request,
        College $college,
        CollegeProgramIntake $intake,
        CollegeProgramIntakeService $service
    ): RedirectResponse {
        $service->addAllocation(
            $intake,
            $college,
            $request->validated(),
            $request->user()->id,
            $request->ip()
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' =>
                'Discipline / Specialization seat allocation added.',
        ]);
    }

    public function updateAllocation(
        UpdateCollegeProgramIntakeAllocationRequest $request,
        College $college,
        CollegeProgramIntake $intake,
        CollegeProgramIntakeAllocation $allocation,
        CollegeProgramIntakeService $service
    ): RedirectResponse {
        $service->updateAllocation(
            $allocation,
            $intake,
            $college,
            $request->validated(),
            $request->user()->id,
            $request->ip()
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Seat allocation updated.',
        ]);
    }

    public function destroyAllocation(
        Request $request,
        College $college,
        CollegeProgramIntake $intake,
        CollegeProgramIntakeAllocation $allocation,
        CollegeProgramIntakeService $service
    ): RedirectResponse {
        $this->authorizeCollege(
            $request,
            $college,
            'college_program_intake.update'
        );

        $service->deleteAllocation(
            $allocation,
            $intake,
            $college,
            $request->user()->id,
            $request->ip()
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Seat allocation removed.',
        ]);
    }

    public function status(
        Request $request,
        College $college,
        CollegeProgramIntake $intake,
        CollegeProgramIntakeService $service
    ): RedirectResponse {
        $status = $request->validate([
            'status' => [
                'required',
                Rule::in(['ACTIVE', 'INACTIVE']),
            ],
        ])['status'];

        $permission = $status === 'ACTIVE'
            ? 'college_program_intake.enable'
            : 'college_program_intake.disable';

        $this->authorizeCollege(
            $request,
            $college,
            $permission
        );

        $service->changeStatus(
            $intake,
            $college,
            $status,
            $request->user()->id,
            $request->ip()
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => $status === 'ACTIVE'
                ? 'Intake / Seat Capacity activated.'
                : 'Intake / Seat Capacity deactivated.',
        ]);
    }

    private function authorizeCollege(
        Request $request,
        College $college,
        string $permission
    ): void {
        abort_unless(
            $request->user()->hasCollegePermission(
                $permission,
                $college->id
            ),
            403
        );
    }
}
