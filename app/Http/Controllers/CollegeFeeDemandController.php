<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Models\FeeDemand;
use App\Services\AcademicPolicyResolverService;
use App\Services\ApplicableFeeDemandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CollegeFeeDemandController extends Controller
{
    public function index(
        Request $request,
        College $college,
        AcademicPolicyResolverService $policyResolver,
        ApplicableFeeDemandService $feeDemandService
    ): Response {
        $this->auth($request, $college, 'college_fee_demand.view');

        $offerings = CollegeProgramOffering::query()
            ->with([
                'college:id,university_id',
                'programTemplate.degree.degreeLevel:id,name,code',
                'academicSession:id,name,code',
                'curriculum.terms:id,curriculum_id,sequence_no,name,status',
            ])
            ->where('college_id', $college->id)
            ->where('status', 'ACTIVE')
            ->orderBy('program_template_id')
            ->get()
            ->map(function (CollegeProgramOffering $offering) use ($policyResolver, $feeDemandService, $college) {
                $policy = null;
                $policyError = null;
                try {
                    $policy = $policyResolver->resolveForOffering($offering);
                } catch (ValidationException $e) {
                    $policyError = collect($e->errors())->flatten()->first();
                }

                return [
                    'id' => $offering->id,
                    'program' => $offering->programTemplate?->name,
                    'program_code' => $offering->programTemplate?->code,
                    'degree' => $offering->programTemplate?->degree?->name,
                    'degree_level' => $offering->programTemplate?->degree?->degreeLevel?->name,
                    'session' => $offering->academicSession?->name,
                    'curriculum_id' => $offering->curriculum_id,
                    'academic_policy' => $policy ? [
                        'id' => $policy->id,
                        'name' => $policy->name,
                        'code' => $policy->code,
                        'version' => $policy->version,
                        'scope_type' => $policy->scope_type,
                    ] : null,
                    'academic_policy_error' => $policyError,
                    'bulk_contexts' => $feeDemandService->bulkContexts($college, $offering, $policy),
                ];
            })
            ->values();

        $demands = FeeDemand::query()
            ->with([
                'admission.application:id,candidate_name,application_no',
                'items.installmentSchedules' => fn ($query) => $query->where('status', 'ACTIVE'),
                'studentBenefits' => fn ($query) => $query
                    ->where('status', 'APPROVED')
                    ->with('items:id,fee_student_benefit_id,fee_demand_item_id,sanctioned_amount'),
            ])
            ->where('college_id', $college->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (FeeDemand $demand) => [
                'id' => $demand->id,
                'demand_no' => $demand->demand_no,
                'admission_no' => $demand->admission?->admission_no,
                'application_no' => $demand->admission?->application?->application_no,
                'candidate_name' => $demand->admission?->application?->candidate_name,
                'billing_period_no' => $demand->billing_period_no,
                'billing_period_label' => $demand->billing_period_label ?: 'Billing Period '.$demand->billing_period_no,
                'demand_context' => $demand->demand_context ?: 'LEGACY',
                'billing_basis_group' => $demand->billing_basis_group,
                'bulk_run_key' => $demand->bulk_run_key,
                'currency' => $demand->currency,
                'total_amount' => $demand->total_amount,
                'mandatory_amount' => $demand->mandatory_amount,
                'enrollment_clearance_amount' => $demand->enrollment_clearance_amount,
                'paid_amount' => $demand->paid_amount,
                'adjusted_amount' => $demand->adjusted_amount,
                'outstanding_amount' => $demand->outstanding_amount,
                'status' => $demand->status,
                'generation_mode' => $demand->generation_mode ?? 'MANUAL_RECOVERY',
                'generated_at' => $demand->generated_at?->format('Y-m-d H:i'),
                'benefit_adjustments' => $demand->studentBenefits->map(fn ($benefit) => [
                    'id' => $benefit->id,
                    'scheme_name' => $benefit->scheme_name_snapshot,
                    'scheme_code' => $benefit->scheme_code_snapshot,
                    'benefit_type' => $benefit->benefit_type_snapshot,
                    'sanctioned_amount' => $benefit->sanctioned_amount,
                    'decided_at' => $benefit->decided_at?->format('Y-m-d H:i'),
                ])->values(),
                'items' => $demand->items->map(function ($item) use ($demand) {
                    $benefitAdjustment = $demand->studentBenefits
                        ->flatMap->items
                        ->where('fee_demand_item_id', $item->id)
                        ->sum(fn ($benefitItem) => (float) ($benefitItem->sanctioned_amount ?? 0));

                    return array_merge($item->only([
                        'id', 'owner_type', 'structure_name', 'fee_head_name', 'fee_head_code', 'purpose', 'charge_basis', 'amount',
                        'is_mandatory', 'is_enrollment_clearance_required', 'installment_allowed', 'is_refundable',
                    ]), [
                        'benefit_adjustment_amount' => number_format($benefitAdjustment, 2, '.', ''),
                        'net_payable_amount' => number_format(max(0, (float) $item->amount - $benefitAdjustment), 2, '.', ''),
                        'installment_schedules' => $item->installmentSchedules->map(fn ($row) => [
                            'id' => $row->id, 'installment_no' => $row->installment_no, 'amount' => $row->amount,
                            'due_date' => $row->due_date?->format('Y-m-d'), 'status' => $row->status,
                        ])->values(),
                    ]);
                }),
            ]);

        return Inertia::render('college-fee-demands/index', [
            'college' => $college->only(['id', 'name', 'code']),
            'offerings' => $offerings,
            'demands' => $demands,
            'can' => [
                'generate' => $request->user()->hasCollegePermission('college_fee_demand.generate', $college->id),
                'cancel' => $request->user()->hasCollegePermission('college_fee_demand.cancel', $college->id),
                'manage_installments' => $request->user()->hasCollegePermission('college_fee_installment.manage', $college->id),
            ],
        ]);
    }

    public function eligibleAdmissions(Request $request, College $college): JsonResponse
    {
        $this->auth($request, $college, 'college_fee_demand.view');

        $validated = $request->validate([
            'offering_id' => ['required', 'integer'],
            'q' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $offering = CollegeProgramOffering::query()
            ->where('college_id', $college->id)
            ->where('status', 'ACTIVE')
            ->findOrFail((int) $validated['offering_id']);

        $search = trim($validated['q']);

        $admissions = Admission::query()
            ->with('application:id,candidate_name,application_no')
            ->where('college_id', $college->id)
            ->where('status', 'CONFIRMED')
            ->whereHas('intake', fn ($q) => $q->where('college_program_offering_id', $offering->id))
            ->where(function ($query) use ($search) {
                $query->where('admission_no', 'like', '%'.$search.'%')
                    ->orWhereHas('application', function ($applicationQuery) use ($search) {
                        $applicationQuery->where('candidate_name', 'like', '%'.$search.'%')
                            ->orWhere('application_no', 'like', '%'.$search.'%');
                    });
            })
            ->orderBy('admission_no')
            ->limit(30)
            ->get()
            ->map(fn (Admission $admission) => [
                'id' => $admission->id,
                'admission_no' => $admission->admission_no,
                'application_no' => $admission->application?->application_no,
                'candidate_name' => $admission->application?->candidate_name,
            ])
            ->values();

        return response()->json(['data' => $admissions]);
    }

    public function store(Request $request, College $college, ApplicableFeeDemandService $service): RedirectResponse
    {
        $this->auth($request, $college, 'college_fee_demand.generate');

        $validated = $request->validate([
            'admission_id' => [
                'required',
                'integer',
                Rule::exists('admissions', 'id')->where(fn ($q) => $q
                    ->where('college_id', $college->id)
                    ->where('status', 'CONFIRMED')),
            ],
        ]);

        $demand = $service->recoverInitial(
            $college,
            Admission::findOrFail($validated['admission_id']),
            $request->user()->id
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Initial demand '.$demand->demand_no.' recovered. New Admission Confirmations generate this demand automatically.',
        ]);
    }

    public function bulkStore(
        Request $request,
        College $college,
        AcademicPolicyResolverService $policyResolver,
        ApplicableFeeDemandService $service
    ): RedirectResponse {
        $this->auth($request, $college, 'college_fee_demand.generate');

        $validated = $request->validate([
            'offering_id' => [
                'required',
                'integer',
                Rule::exists('college_program_offerings', 'id')->where(fn ($q) => $q
                    ->where('college_id', $college->id)
                    ->where('status', 'ACTIVE')),
            ],
            'purpose' => ['required', Rule::in(['ACADEMIC', 'EXAMINATION', 'OTHER'])],
            'basis_group' => ['required', Rule::in(['TERM', 'ACADEMIC_YEAR', 'ONE_TIME'])],
            'period_no' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $offering = CollegeProgramOffering::query()
            ->with(['college:id,university_id', 'programTemplate.degree.degreeLevel:id', 'curriculum.terms'])
            ->whereKey($validated['offering_id'])
            ->where('college_id', $college->id)
            ->firstOrFail();

        $policy = null;
        try {
            $policy = $policyResolver->resolveForOffering($offering);
        } catch (ValidationException $e) {
            // First Academic period does not depend on previous progression.
            // Later periods remain blocked by the service regardless, so a policy conflict
            // must not prevent the valid first-period bulk bridge from being used.
            if (! ($validated['purpose'] === 'ACADEMIC' && (int) $validated['period_no'] === 1)) {
                throw $e;
            }
        }

        $result = $service->generateBulk(
            $college,
            $offering,
            $validated['purpose'],
            $validated['basis_group'],
            (int) $validated['period_no'],
            $request->user()->id,
            $policy
        );

        if ($result['created'] === 0 && $result['skipped'] > 0 && ! $result['errors']) {
            $message = $result['label'].': no new demands generated. '
                .$result['skipped'].' eligible candidate(s) skipped because all applicable fee items are already covered by active demand(s).';
            $type = 'info';
        } else {
            $message = $result['label'].': '.$result['created'].' demand(s) generated';
            if ($result['skipped']) {
                $message .= ', '.$result['skipped'].' skipped because all applicable fee items are already covered by active demand(s)';
            }
            if ($result['errors']) {
                $message .= '. '.count($result['errors']).' candidate(s) could not be generated.';
            }
            $type = $result['errors'] ? 'warning' : 'success';
        }

        return back()->with('toast', [
            'type' => $type,
            'message' => $message,
        ]);
    }

    public function individualStore(
        Request $request,
        College $college,
        AcademicPolicyResolverService $policyResolver,
        ApplicableFeeDemandService $service
    ): RedirectResponse {
        $this->auth($request, $college, 'college_fee_demand.generate');

        $validated = $request->validate([
            'offering_id' => [
                'required',
                'integer',
                Rule::exists('college_program_offerings', 'id')->where(fn ($q) => $q
                    ->where('college_id', $college->id)
                    ->where('status', 'ACTIVE')),
            ],
            'admission_id' => [
                'required',
                'integer',
                Rule::exists('admissions', 'id')->where(fn ($q) => $q
                    ->where('college_id', $college->id)
                    ->where('status', 'CONFIRMED')),
            ],
            'purpose' => ['required', Rule::in(['ACADEMIC', 'EXAMINATION', 'OTHER'])],
            'basis_group' => ['required', Rule::in(['TERM', 'ACADEMIC_YEAR', 'ONE_TIME'])],
            'period_no' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $offering = CollegeProgramOffering::query()
            ->with(['college:id,university_id', 'programTemplate.degree.degreeLevel:id', 'curriculum.terms'])
            ->whereKey($validated['offering_id'])
            ->where('college_id', $college->id)
            ->firstOrFail();

        $admission = Admission::query()
            ->with(['intake.offering', 'application'])
            ->whereKey($validated['admission_id'])
            ->where('college_id', $college->id)
            ->where('status', 'CONFIRMED')
            ->firstOrFail();

        if ((int) $admission->intake?->college_program_offering_id !== (int) $offering->id) {
            throw ValidationException::withMessages([
                'admission_id' => 'Select a CONFIRMED admission from the selected Program Offering.',
            ]);
        }

        $policy = null;
        try {
            $policy = $policyResolver->resolveForOffering($offering);
        } catch (ValidationException $e) {
            if (! ($validated['purpose'] === 'ACADEMIC' && (int) $validated['period_no'] === 1)) {
                throw $e;
            }
        }

        try {
            $demand = $service->generateIndividual(
                $college,
                $offering,
                $admission,
                $validated['purpose'],
                $validated['basis_group'],
                (int) $validated['period_no'],
                $request->user()->id,
                $policy
            );
        } catch (ValidationException $e) {
            $message = (string) collect($e->errors())->flatten()->first();

            if (str_contains($message, 'No new applicable fee item remains')) {
                $candidate = $admission->application?->candidate_name ?: $admission->admission_no;

                return back()->with('toast', [
                    'type' => 'info',
                    'message' => 'No new demand created for '.$candidate.'. All applicable fee items in the selected billing period are already covered by active demand(s).',
                ]);
            }

            throw $e;
        }

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Individual demand '.$demand->demand_no.' generated for '.$demand->billing_period_label.'.',
        ]);
    }

    public function cancel(Request $request, College $college, FeeDemand $demand): RedirectResponse
    {
        $this->auth($request, $college, 'college_fee_demand.cancel');
        abort_unless($demand->college_id === $college->id, 404);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        if ((float) $demand->paid_amount > 0 || (float) $demand->adjusted_amount > 0) {
            return back()->withErrors([
                'demand' => 'Demand with payment/adjustment activity cannot be cancelled. Use the future reversal/adjustment workflow.',
            ]);
        }
        if ($demand->status === 'CANCELLED') {
            return back();
        }

        $demand->update([
            'status' => 'CANCELLED',
            'cancelled_at' => now(),
            'cancelled_by' => $request->user()->id,
            'cancellation_reason' => $validated['reason'],
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Fee Demand cancelled. Historical snapshot is retained.']);
    }

    private function auth(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
