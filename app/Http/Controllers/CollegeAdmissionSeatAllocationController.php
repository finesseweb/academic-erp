<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\CollegeAdmissionMeritEntry;
use App\Models\CollegeAdmissionSeatAllocation;
use App\Models\CollegeAdmissionSelectionRule;
use App\Services\CollegeAdmissionSeatAllocationService;
use App\Services\CollegeReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollegeAdmissionSeatAllocationController extends Controller
{
    public function index(
        Request $request,
        College $college,
        CollegeAdmissionSeatAllocationService $service,
        CollegeReservationService $reservationService
    ): Response {
        $this->authorizeCollege($request, $college, 'college_admission_seat_allocation.view');

        $ruleIds = CollegeAdmissionMeritEntry::query()
            ->where('college_id', $college->id)
            ->distinct()
            ->pluck('college_admission_selection_rule_id');

        $rules = CollegeAdmissionSelectionRule::query()
            ->whereIn('id', $ruleIds)
            ->with([
                'intake.offering.programTemplate.degree.degreeLevel:id,name,code',
                'intake.offering.academicSession:id,name,code,is_current',
                'reservationPlan:id,college_program_intake_id,bucket_type,bucket_key,basis_capacity,status',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(function (CollegeAdmissionSelectionRule $rule) use ($reservationService) {
                $bucket = $reservationService->availableBuckets($rule->intake)->firstWhere('bucket_key', $rule->bucket_key);
                $program = $rule->intake?->offering?->programTemplate;
                $degree = $program?->degree;
                $degreeLevel = $degree?->degreeLevel;

                return [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'code' => $rule->code,
                    'version_no' => $rule->version_no,
                    'status' => $rule->status,
                    'bucket_key' => $rule->bucket_key,
                    'bucket_label' => $bucket['label'] ?? $rule->bucket_key,
                    'basis_capacity' => (int) $rule->basis_capacity,
                    'program_name' => $program?->name,
                    'program_code' => $program?->code,
                    'degree_level_name' => $degreeLevel?->name,
                    'degree_name' => $degree?->name,
                    'session_name' => $rule->intake?->offering?->academicSession?->name,
                    'reservation_plan_id' => $rule->college_program_reservation_plan_id,
                    'reservation_plan_status' => $rule->reservationPlan?->status,
                ];
            })
            ->values();

        $selectedRuleId = (int) $request->query('rule_id', $rules->first()['id'] ?? 0);
        $selectedRule = $selectedRuleId > 0
            ? CollegeAdmissionSelectionRule::query()->find($selectedRuleId)
            : null;

        if ($selectedRule && ! $rules->contains(fn ($row) => (int) $row['id'] === (int) $selectedRule->id)) {
            $selectedRule = null;
        }

        return Inertia::render('college-admission-seat-allocations/index', [
            'college' => $college->only(['id', 'name', 'code', 'status']),
            'rules' => $rules,
            'selectedRuleId' => $selectedRule?->id,
            'screen' => $selectedRule ? $service->screen($college, $selectedRule) : null,
            'can' => [
                'allocate' => $request->user()->hasCollegePermission('college_admission_seat_allocation.allocate', $college->id),
                'cancel' => $request->user()->hasCollegePermission('college_admission_seat_allocation.cancel', $college->id),
            ],
        ]);
    }

    public function allocate(
        Request $request,
        College $college,
        CollegeAdmissionMeritEntry $meritEntry,
        CollegeAdmissionSeatAllocationService $service
    ): RedirectResponse {
        $this->authorizeCollege($request, $college, 'college_admission_seat_allocation.allocate');

        $data = $request->validate([
            'physical_reservation_category_id' => ['nullable', 'integer'],
            'horizontal_category_ids' => ['nullable', 'array'],
            'horizontal_category_ids.*' => ['integer'],
            'horizontal_target_category_ids' => ['nullable', 'array'],
            'horizontal_target_category_ids.*' => ['integer'],
            'allocation_round' => ['nullable', 'integer', 'min:1', 'max:999'],
            'decision_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $allocation = $service->allocate($college, $meritEntry, $data, $request->user()->id, $request->ip());

        return redirect()
            ->route('college-admission-seat-allocations.index', [
                'college' => $college->id,
                'rule_id' => $allocation->college_admission_selection_rule_id,
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Seat allocated to Merit Rank #'.$allocation->merit_rank.'. Physical seat consumption is now recorded and protected for Admission Confirmation.',
            ]);
    }

    public function cancel(
        Request $request,
        College $college,
        CollegeAdmissionSeatAllocation $allocation,
        CollegeAdmissionSeatAllocationService $service
    ): RedirectResponse {
        $this->authorizeCollege($request, $college, 'college_admission_seat_allocation.cancel');
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $ruleId = $allocation->college_admission_selection_rule_id;
        $service->cancel($college, $allocation, $data['reason'], $request->user()->id, $request->ip());

        return redirect()
            ->route('college-admission-seat-allocations.index', [
                'college' => $college->id,
                'rule_id' => $ruleId,
            ])
            ->with('toast', [
                'type' => 'success',
                'message' => 'Seat allocation cancelled. The physical seat is available again for this admission bucket.',
            ]);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
