<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollegeReservationAllocationRequest;
use App\Http\Requests\StoreCollegeReservationPlanRequest;
use App\Http\Requests\UpdateCollegeReservationAllocationRequest;
use App\Http\Requests\UpdateCollegeReservationPlanRequest;
use App\Models\College;
use App\Models\CollegeProgramIntake;
use App\Models\CollegeProgramReservationAllocation;
use App\Models\CollegeProgramReservationPlan;
use App\Models\ReservationCategory;
use App\Services\CollegeReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeReservationController extends Controller
{
    public function index(
        Request $request,
        College $college,
        CollegeReservationService $service
    ): Response {
        $this->authorizeCollege($request, $college, 'college_reservation.view');

        $intakes = CollegeProgramIntake::query()
            ->with([
                'offering.programTemplate:id,name,code',
                'offering.academicSession:id,name,code,is_current',
                'allocations.discipline:id,name,code',
                'allocations.specialization:id,name,code',
            ])
            ->where('status', 'ACTIVE')
            ->whereHas('offering', fn ($query) => $query->where('college_id', $college->id))
            ->get();

        $plans = CollegeProgramReservationPlan::query()
            ->with([
                'intake.offering.programTemplate:id,name,code',
                'intake.offering.academicSession:id,name,code,is_current',
                'disciplineAllocation.discipline:id,name,code',
                'specializationAllocation.specialization:id,name,code',
                'allocations.category:id,name,code,nature,display_order',
            ])
            ->whereHas('intake.offering', fn ($query) => $query->where('college_id', $college->id))
            ->orderByDesc('id')
            ->get();

        $used = $plans->groupBy('college_program_intake_id')
            ->map(fn ($rows) => $rows->pluck('bucket_key'));

        $availableBuckets = $intakes
            ->flatMap(function ($intake) use ($service, $used) {
                $usedKeys = $used->get($intake->id, collect());

                return $service->availableBuckets($intake)
                    ->reject(fn ($bucket) => $usedKeys->contains($bucket['bucket_key']))
                    ->map(fn ($bucket) => [
                        ...$bucket,
                        'college_program_intake_id' => $intake->id,
                        'program_name' => $intake->offering->programTemplate->name,
                        'session_name' => $intake->offering->academicSession->name,
                        'is_current_session' => (bool) $intake->offering->academicSession->is_current,
                    ]);
            })
            ->sortBy([
                fn ($a, $b) => (int) $b['is_current_session'] <=> (int) $a['is_current_session'],
                fn ($a, $b) => strcmp($a['program_name'], $b['program_name']),
                fn ($a, $b) => strcmp($a['label'], $b['label']),
            ])
            ->values();

        $categories = ReservationCategory::query()
            ->where('university_id', $college->university_id)
            ->where('status', 'ACTIVE')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id','name','code','nature','display_order']);

        return Inertia::render('college-reservations/index', [
            'college' => $college->only(['id','name','code','status']),
            'plans' => $plans,
            'availableBuckets' => $availableBuckets,
            'categories' => $categories,
            'can' => [
                'create' => $request->user()->hasCollegePermission('college_reservation.create', $college->id),
                'update' => $request->user()->hasCollegePermission('college_reservation.update', $college->id),
                // Reservation lifecycle must not disappear for a role that is already
                // authorized to maintain the plan. Explicit lifecycle permissions are
                // still honoured, while update permission provides the management fallback.
                'enable' => $request->user()->hasCollegePermission('college_reservation.enable', $college->id)
                    || $request->user()->hasCollegePermission('college_reservation.update', $college->id),
                'disable' => $request->user()->hasCollegePermission('college_reservation.disable', $college->id)
                    || $request->user()->hasCollegePermission('college_reservation.update', $college->id),
            ],
        ]);
    }

    public function store(
        StoreCollegeReservationPlanRequest $request,
        College $college,
        CollegeReservationService $service
    ): RedirectResponse {
        $service->createPlan(
            $college,
            $request->validated(),
            $request->user()->id,
            $request->ip()
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Reservation / Seat Distribution plan created. Add quota allocations, then activate it.',
        ]);
    }

    public function update(
        UpdateCollegeReservationPlanRequest $request,
        College $college,
        CollegeProgramReservationPlan $plan,
        CollegeReservationService $service
    ): RedirectResponse {
        $service->updatePlan(
            $plan,
            $college,
            $request->validated(),
            $request->user()->id,
            $request->ip()
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Reservation / Seat Distribution plan updated.',
        ]);
    }

    public function storeAllocation(
        StoreCollegeReservationAllocationRequest $request,
        College $college,
        CollegeProgramReservationPlan $plan,
        CollegeReservationService $service
    ): RedirectResponse {
        $service->addAllocation(
            $plan,
            $college,
            $request->validated(),
            $request->user()->id,
            $request->ip()
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Reservation / Quota allocation added.',
        ]);
    }

    public function updateAllocation(
        UpdateCollegeReservationAllocationRequest $request,
        College $college,
        CollegeProgramReservationPlan $plan,
        CollegeProgramReservationAllocation $allocation,
        CollegeReservationService $service
    ): RedirectResponse {
        $service->updateAllocation(
            $allocation,
            $plan,
            $college,
            $request->validated(),
            $request->user()->id,
            $request->ip()
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Reservation / Quota allocation updated.',
        ]);
    }

    public function destroyAllocation(
        Request $request,
        College $college,
        CollegeProgramReservationPlan $plan,
        CollegeProgramReservationAllocation $allocation,
        CollegeReservationService $service
    ): RedirectResponse {
        $this->authorizeCollege($request, $college, 'college_reservation.update');

        $service->deleteAllocation(
            $allocation,
            $plan,
            $college,
            $request->user()->id,
            $request->ip()
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Reservation / Quota allocation removed.',
        ]);
    }

    public function status(
        Request $request,
        College $college,
        CollegeProgramReservationPlan $plan,
        CollegeReservationService $service
    ): RedirectResponse {
        $status = $request->validate([
            'status' => ['required', Rule::in(['ACTIVE','INACTIVE'])],
        ])['status'];

        $lifecyclePermission = $status === 'ACTIVE'
            ? 'college_reservation.enable'
            : 'college_reservation.disable';

        abort_unless(
            $request->user()->hasCollegePermission($lifecyclePermission, $college->id)
                || $request->user()->hasCollegePermission('college_reservation.update', $college->id),
            403
        );

        $service->changeStatus(
            $plan,
            $college,
            $status,
            $request->user()->id,
            $request->ip()
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => $status === 'ACTIVE'
                ? 'Reservation / Seat Distribution activated.'
                : 'Reservation / Seat Distribution deactivated.',
        ]);
    }

    private function authorizeCollege(
        Request $request,
        College $college,
        string $permission
    ): void {
        abort_unless(
            $request->user()->hasCollegePermission($permission, $college->id),
            403
        );
    }
}
