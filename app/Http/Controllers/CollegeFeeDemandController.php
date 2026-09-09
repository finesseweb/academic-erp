<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\AcademicSession;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Models\FeeDemand;
use App\Services\AcademicPolicyResolverService;
use App\Services\ApplicableFeeDemandService;
use App\Services\FeeLateFineService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        ApplicableFeeDemandService $feeDemandService,
        FeeLateFineService $lateFineService
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
                    'session' => $offering->academicSession?->name,
                    'academic_session_id' => $offering->academic_session_id,
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
                    'installment_contexts' => $feeDemandService->installmentContexts($college, $offering),
                ];
            })
            ->values();

        $sessions = AcademicSession::query()
            ->where('university_id', $college->university_id)
            ->where('status', 'ACTIVE')
            ->orderByDesc('is_current')
            ->orderByDesc('starts_on')
            ->get(['id', 'name', 'code', 'is_current']);
        $currentSessionId = (int) ($sessions->firstWhere('is_current', true)?->id ?? $sessions->first()?->id ?? 0);
        $registerSessionId = (int) $request->query('session_id', $currentSessionId);
        $registerOfferingId = (int) $request->query('register_offering_id', 0);
        $registerDisciplineId = (int) $request->query('register_discipline_id', 0);
        $registerStatus = strtoupper(trim((string) $request->query('register_status', '')));

        $registerSearch = trim((string) $request->query('register_q', ''));
        $registerPerPage = (int) $request->query('per_page', 25);
        if (! in_array($registerPerPage, [25, 50, 100], true)) {
            $registerPerPage = 25;
        }

        $registerDisciplines = DB::table('college_admission_application_academic_preferences as pref')
            ->join('college_admission_applications as a', 'a.id', '=', 'pref.college_admission_application_id')
            ->join('college_program_offerings as cpo', 'cpo.id', '=', 'pref.college_program_offering_id')
            ->join('academic_disciplines as disc', 'disc.id', '=', 'pref.discipline_id')
            ->where('a.college_id', $college->id)
            ->when($registerSessionId > 0, fn ($query) => $query->where('cpo.academic_session_id', $registerSessionId))
            ->when($registerOfferingId > 0, fn ($query) => $query->where('pref.college_program_offering_id', $registerOfferingId))
            ->select('disc.id', 'disc.name', 'disc.code')
            ->distinct()
            ->orderBy('disc.name')
            ->get();

        if ($registerDisciplineId > 0 && ! $registerDisciplines->contains(fn ($discipline) => (int) $discipline->id === $registerDisciplineId)) {
            $registerDisciplineId = 0;
        }

        // Register scalability rule (ADR 145): paginate admission/application groups on the server.
        // Detailed demands are loaded only for admissions visible on the current register page.
        $groupQuery = FeeDemand::query()
            ->select('admission_id')
            ->where('college_id', $college->id)
            ->whereNotNull('admission_id')
            ->when($registerSessionId > 0, fn ($query) => $query->where('academic_session_id', $registerSessionId))
            ->when($registerOfferingId > 0, fn ($query) => $query->where('college_program_offering_id', $registerOfferingId))
            ->when($registerDisciplineId > 0, fn ($query) => $query->whereHas('admission.application.academicPreference', fn ($preference) => $preference->where('discipline_id', $registerDisciplineId)))
            ->when($registerStatus !== '', fn ($query) => $query->where('status', $registerStatus), fn ($query) => $query->where('status', '!=', 'CANCELLED'))
            ->when($registerSearch !== '', function ($query) use ($registerSearch) {
                $query->where(function ($searchQuery) use ($registerSearch) {
                    $searchQuery->where('demand_no', 'like', '%'.$registerSearch.'%')
                        ->orWhere('billing_period_label', 'like', '%'.$registerSearch.'%')
                        ->orWhereHas('admission', function ($admissionQuery) use ($registerSearch) {
                            $admissionQuery->where('admission_no', 'like', '%'.$registerSearch.'%')
                                ->orWhereHas('application', function ($applicationQuery) use ($registerSearch) {
                                    $applicationQuery->where('candidate_name', 'like', '%'.$registerSearch.'%')
                                        ->orWhere('application_no', 'like', '%'.$registerSearch.'%')
                                        ->orWhereHas('academicPreference.discipline', function ($discipline) use ($registerSearch) {
                                            $discipline->where('name', 'like', '%'.$registerSearch.'%')
                                                ->orWhere('code', 'like', '%'.$registerSearch.'%');
                                        });
                                });
                        });
                });
            })
            ->groupBy('admission_id')
            ->orderByDesc('admission_id')
            ->paginate($registerPerPage)
            ->withQueryString();

        $visibleAdmissionIds = collect($groupQuery->items())->pluck('admission_id')->filter()->values();

        $offeringLabels = $offerings->keyBy('id');

        $demandModels = FeeDemand::query()
            ->with([
                'admission.application:id,candidate_name,application_no,date_of_birth',
                'admission.application.academicPreference.discipline:id,name,code',
                'items.installmentSchedules' => fn ($query) => $query->where('status', 'ACTIVE'),
                'studentBenefits' => fn ($query) => $query
                    ->where('status', 'APPROVED')
                    ->with('items:id,fee_student_benefit_id,fee_demand_item_id,sanctioned_amount'),
            ])
            ->where('college_id', $college->id)
            ->whereIn('admission_id', $visibleAdmissionIds)
            ->when($registerSessionId > 0, fn ($query) => $query->where('academic_session_id', $registerSessionId))
            ->when($registerOfferingId > 0, fn ($query) => $query->where('college_program_offering_id', $registerOfferingId))
            ->when($registerDisciplineId > 0, fn ($query) => $query->whereHas('admission.application.academicPreference', fn ($preference) => $preference->where('discipline_id', $registerDisciplineId)))
            ->when($registerStatus !== '', fn ($query) => $query->where('status', $registerStatus), fn ($query) => $query->where('status', '!=', 'CANCELLED'))
            ->orderByDesc('id')
            ->get();

        $lateFineByDemand = $lateFineService->activeFineForDemandIds($demandModels->pluck('id')->all());

        $demands = $demandModels->map(fn (FeeDemand $demand) => [
                'id' => $demand->id,
                'demand_no' => $demand->demand_no,
                'admission_no' => $demand->admission?->admission_no,
                'application_no' => $demand->admission?->application?->application_no,
                'candidate_name' => $demand->admission?->application?->candidate_name,
                'discipline_id' => $demand->admission?->application?->academicPreference?->discipline?->id,
                'discipline_name' => $demand->admission?->application?->academicPreference?->discipline?->name,
                'discipline_code' => $demand->admission?->application?->academicPreference?->discipline?->code,
                'age' => $demand->admission?->application?->date_of_birth ? Carbon::parse($demand->admission->application->date_of_birth)->age : null,
                'programme_name' => data_get($offeringLabels->get($demand->college_program_offering_id), 'program'),
                'programme_code' => data_get($offeringLabels->get($demand->college_program_offering_id), 'program_code'),
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
                'late_fine_amount' => number_format((float) ($lateFineByDemand[$demand->id] ?? 0), 2, '.', ''),
                'payable_with_late_fine' => number_format((float) $demand->outstanding_amount + (float) ($lateFineByDemand[$demand->id] ?? 0), 2, '.', ''),
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
            'register_disciplines' => $registerDisciplines,
            'register_sessions' => $sessions->map(fn ($session) => ['id' => $session->id, 'name' => $session->name, 'code' => $session->code, 'is_current' => (bool) $session->is_current])->values(),
            'register' => [
                'q' => $registerSearch,
                'session_id' => $registerSessionId,
                'offering_id' => $registerOfferingId,
                'discipline_id' => $registerDisciplineId,
                'status' => $registerStatus,
                'per_page' => $registerPerPage,
                'current_page' => $groupQuery->currentPage(),
                'last_page' => $groupQuery->lastPage(),
                'from' => $groupQuery->firstItem(),
                'to' => $groupQuery->lastItem(),
                'total' => $groupQuery->total(),
            ],
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

        DB::transaction(function () use ($demand,$request,$validated) {
            if (\Illuminate\Support\Facades\Schema::hasTable('fee_late_fine_charges')) {
                DB::table('fee_late_fine_charges')->where('fee_demand_id',$demand->id)->where('status','ACTIVE')->update(['status'=>'REVERSED','superseded_at'=>now(),'updated_at'=>now()]);
            }
            if (\Illuminate\Support\Facades\Schema::hasTable('fee_installment_schedules')) {
                DB::table('fee_installment_schedules')->where('fee_demand_id',$demand->id)->where('status','ACTIVE')->update([
                    'status'=>'CANCELLED','cancelled_at'=>now(),'cancelled_by'=>$request->user()->id,
                    'cancellation_reason'=>'Automatically cancelled because Fee Demand was cancelled.','updated_at'=>now(),
                ]);
            }
            $demand->update([
                'status' => 'CANCELLED',
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()->id,
                'cancellation_reason' => $validated['reason'],
            ]);
        });

        return back()->with('toast', ['type' => 'success', 'message' => 'Fee Demand cancelled. Historical snapshot is retained.']);
    }

    private function auth(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
