<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Models\FeeDemand;
use App\Models\FeeScholarshipScheme;
use App\Models\FeeStudentBenefit;
use App\Services\FeeStudentBenefitService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeFeeStudentBenefitController extends Controller
{
    public function index(Request $request, College $college, FeeStudentBenefitService $service): Response
    {
        $this->auth($request, $college, 'college_fee_student_benefit.view');

        $sessions = AcademicSession::query()
            ->where('university_id', $college->university_id)
            ->orderByDesc('is_current')
            ->orderByDesc('starts_on')
            ->get(['id','name','code','status','is_current']);

        $currentSession = $sessions->firstWhere('is_current', true)
            ?? $sessions->firstWhere('status', 'ACTIVE')
            ?? $sessions->first();

        $requestedSessionId = (int) $request->query('session_id', 0);
        $selectedSession = $requestedSessionId > 0
            ? $sessions->firstWhere('id', $requestedSessionId)
            : $currentSession;
        $sessionId = $selectedSession?->id;

        $offeringId = max(0, (int) $request->query('offering_id', 0));
        $disciplineId = max(0, (int) $request->query('discipline_id', 0));
        $schemeId = max(0, (int) $request->query('scheme_id', 0));
        $billingPeriod = trim((string) $request->query('billing_period', ''));
        $status = strtoupper(trim((string) $request->query('status', '')));
        $search = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 25);
        if (! in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }
        if (! in_array($status, ['PENDING','APPROVED','REJECTED','CANCELLED'], true)) {
            $status = '';
        }

        $offerings = CollegeProgramOffering::query()
            ->with('programTemplate:id,name,code')
            ->where('college_id', $college->id)
            ->when($sessionId, fn ($query) => $query->where('academic_session_id', $sessionId))
            ->orderByDesc('status')
            ->orderBy('id')
            ->get(['id','program_template_id','academic_session_id','status'])
            ->map(fn (CollegeProgramOffering $offering) => [
                'id' => $offering->id,
                'label' => trim(($offering->programTemplate?->name ?? 'Programme').' ('.($offering->programTemplate?->code ?? 'N/A').')'),
                'status' => $offering->status,
            ])
            ->values();

        if ($offeringId > 0 && ! $offerings->contains(fn ($offering) => (int) $offering['id'] === $offeringId)) {
            $offeringId = 0;
        }

        $disciplines = DB::table('college_admission_application_academic_preferences as pref')
            ->join('college_admission_applications as a', 'a.id', '=', 'pref.college_admission_application_id')
            ->join('academic_disciplines as disc', 'disc.id', '=', 'pref.discipline_id')
            ->join('college_program_offerings as cpo', 'cpo.id', '=', 'pref.college_program_offering_id')
            ->where('a.college_id', $college->id)
            ->when($sessionId, fn ($query) => $query->where('cpo.academic_session_id', $sessionId))
            ->when($offeringId > 0, fn ($query) => $query->where('pref.college_program_offering_id', $offeringId))
            ->select('disc.id', 'disc.name', 'disc.code')
            ->distinct()
            ->orderBy('disc.name')
            ->get();

        if ($disciplineId > 0 && ! $disciplines->contains(fn ($discipline) => (int) $discipline->id === $disciplineId)) {
            $disciplineId = 0;
        }

        $billingPeriods = DB::table('fee_demands')
            ->where('college_id', $college->id)
            ->when($sessionId, fn ($query) => $query->where('academic_session_id', $sessionId))
            ->when($offeringId > 0, fn ($query) => $query->where('college_program_offering_id', $offeringId))
            ->whereNotNull('billing_period_label')
            ->where('billing_period_label', '<>', '')
            ->distinct()
            ->orderBy('billing_period_label')
            ->pluck('billing_period_label')
            ->values();

        $schemeOptions = FeeScholarshipScheme::query()
            ->where('university_id', $college->university_id)
            ->where(function ($query) use ($college) {
                $query->whereNull('college_id')->orWhere('college_id', $college->id);
            })
            ->when($sessionId, fn ($query) => $query->where('academic_session_id', $sessionId))
            ->orderByRaw('college_id is null desc')
            ->orderBy('name')
            ->get(['id','name','code'])
            ->map(fn (FeeScholarshipScheme $scheme) => [
                'id' => $scheme->id,
                'label' => $scheme->name.' ('.$scheme->code.')',
            ])
            ->values();

        $applyBenefitFilters = function ($query) use ($sessionId, $offeringId, $disciplineId, $billingPeriod, $schemeId, $status, $search) {
            return $query
                ->when($sessionId, fn ($q) => $q->whereHas('demand', fn ($demand) => $demand->where('academic_session_id', $sessionId)))
                ->when($offeringId > 0, fn ($q) => $q->whereHas('demand', fn ($demand) => $demand->where('college_program_offering_id', $offeringId)))
                ->when($disciplineId > 0, fn ($q) => $q->whereHas('demand.admission.application.academicPreference', fn ($preference) => $preference->where('discipline_id', $disciplineId)))
                ->when($billingPeriod !== '', fn ($q) => $q->whereHas('demand', fn ($demand) => $demand->where('billing_period_label', $billingPeriod)))
                ->when($schemeId > 0, fn ($q) => $q->where('fee_scholarship_scheme_id', $schemeId))
                ->when($status !== '', fn ($q) => $q->where('status', $status))
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($inner) use ($search) {
                        $inner->where('scheme_name_snapshot', 'like', '%'.$search.'%')
                            ->orWhere('scheme_code_snapshot', 'like', '%'.$search.'%')
                            ->orWhere('status', 'like', '%'.$search.'%')
                            ->orWhereHas('demand', function ($demand) use ($search) {
                                $demand->where('demand_no', 'like', '%'.$search.'%')
                                    ->orWhereHas('admission', function ($admission) use ($search) {
                                        $admission->where('admission_no', 'like', '%'.$search.'%')
                                            ->orWhereHas('application', function ($application) use ($search) {
                                                $application->where('application_no', 'like', '%'.$search.'%')
                                                    ->orWhere('candidate_name', 'like', '%'.$search.'%')
                                                    ->orWhereHas('academicPreference.discipline', function ($discipline) use ($search) {
                                                        $discipline->where('name', 'like', '%'.$search.'%')
                                                            ->orWhere('code', 'like', '%'.$search.'%');
                                                    });
                                            });
                                    });
                            });
                    });
                });
        };

        // ADR 156: paginate one operational parent row per admission/application, not per Fee Demand.
        // Individual and bulk-created benefits are intentionally merged at register level when they belong
        // to the same admission; their exact Fee Demand / billing period remains visible in child rows.
        $filteredForGrouping = $applyBenefitFilters(
            FeeStudentBenefit::query()
                ->select(['id','fee_demand_id'])
                ->where('college_id', $college->id)
        );

        $groupQuery = DB::query()
            ->fromSub($filteredForGrouping->toBase(), 'filtered_benefits')
            ->join('fee_demands as group_demand', 'group_demand.id', '=', 'filtered_benefits.fee_demand_id')
            ->select('group_demand.admission_id')
            ->whereNotNull('group_demand.admission_id')
            ->groupBy('group_demand.admission_id')
            ->orderByRaw('MAX(filtered_benefits.id) DESC')
            ->paginate($perPage)
            ->withQueryString();

        $visibleAdmissionIds = collect($groupQuery->items())->pluck('admission_id')->filter()->values();

        $benefits = $applyBenefitFilters(
            FeeStudentBenefit::query()
                ->with(['items.demandItem.installmentSchedules'])
                ->where('college_id', $college->id)
                ->whereHas('demand', fn ($demand) => $demand->whereIn('admission_id', $visibleAdmissionIds))
        )
            ->orderByDesc('id')
            ->get();

        $visibleDemandIds = $benefits->pluck('fee_demand_id')->filter()->unique()->values();

        $demandContext = DB::table('fee_demands as d')
            ->leftJoin('admissions as ad', 'ad.id', '=', 'd.admission_id')
            ->leftJoin('college_admission_applications as a', 'a.id', '=', 'ad.college_admission_application_id')
            ->leftJoin('college_admission_application_academic_preferences as pref', 'pref.college_admission_application_id', '=', 'a.id')
            ->leftJoin('academic_disciplines as disc', 'disc.id', '=', 'pref.discipline_id')
            ->leftJoin('college_program_offerings as cpo', 'cpo.id', '=', 'd.college_program_offering_id')
            ->leftJoin('program_templates as pt', 'pt.id', '=', 'cpo.program_template_id')
            ->whereIn('d.id', $visibleDemandIds)
            ->select([
                'd.id','d.admission_id','d.demand_no','d.total_amount','d.adjusted_amount','d.outstanding_amount','d.status as demand_status','d.billing_period_label',
                'd.academic_session_id','d.college_program_offering_id','ad.admission_no','a.application_no','a.candidate_name','a.date_of_birth',
                'disc.id as discipline_id','disc.name as discipline_name','disc.code as discipline_code',
                'pt.name as programme_name','pt.code as programme_code',
            ])
            ->get()->keyBy('id');

        return Inertia::render('college-fee-benefits/index', [
            'college' => $college->only(['id','name','code']),
            'bulk_schemes' => $service->bulkSchemes($college),
            'benefits' => $benefits->map(function (FeeStudentBenefit $benefit) use ($demandContext, $service) {
                $d = $demandContext[$benefit->fee_demand_id] ?? null;
                $age = $d?->date_of_birth ? Carbon::parse($d->date_of_birth)->age : null;
                return [
                    'id' => $benefit->id,
                    'fee_demand_id' => $benefit->fee_demand_id,
                    'admission_id' => $d?->admission_id,
                    'demand_no' => $d?->demand_no,
                    'demand_status' => $d?->demand_status,
                    'billing_period_label' => $d?->billing_period_label,
                    'academic_session_id' => $d?->academic_session_id,
                    'college_program_offering_id' => $d?->college_program_offering_id,
                    'programme_name' => $d?->programme_name,
                    'programme_code' => $d?->programme_code,
                    'discipline_id' => $d?->discipline_id,
                    'discipline_name' => $d?->discipline_name,
                    'discipline_code' => $d?->discipline_code,
                    'age' => $age,
                    'admission_no' => $d?->admission_no,
                    'application_no' => $d?->application_no,
                    'candidate_name' => $d?->candidate_name,
                    'scheme_name' => $benefit->scheme_name_snapshot,
                    'scheme_code' => $benefit->scheme_code_snapshot,
                    'benefit_type' => $benefit->benefit_type_snapshot,
                    'application_mode' => $benefit->application_mode,
                    'status' => $benefit->status,
                    'eligible_base_amount' => $benefit->eligible_base_amount,
                    'calculated_benefit_amount' => $benefit->calculated_benefit_amount,
                    'sanctioned_amount' => $benefit->sanctioned_amount,
                    'application_note' => $benefit->application_note,
                    'decision_note' => $benefit->decision_note,
                    'applied_at' => $benefit->applied_at?->format('Y-m-d H:i'),
                    'decided_at' => $benefit->decided_at?->format('Y-m-d H:i'),
                    'demand_total_amount' => $d?->total_amount,
                    'demand_adjusted_amount' => $d?->adjusted_amount,
                    'demand_outstanding_amount' => $d?->outstanding_amount,
                    'eligibility_snapshot' => $benefit->eligibility_snapshot,
                    'installment_context' => $benefit->status === 'PENDING' ? $service->installmentContext($benefit) : null,
                    'installment_adjustment_mode' => $benefit->installment_adjustment_mode,
                    'installment_adjustment_snapshot' => $benefit->installment_adjustment_snapshot,
                ];
            })->values(),
            'register_filters' => [
                'session_id' => $sessionId,
                'offering_id' => $offeringId ?: null,
                'discipline_id' => $disciplineId ?: null,
                'billing_period' => $billingPeriod,
                'scheme_id' => $schemeId ?: null,
                'status' => $status,
                'q' => $search,
                'per_page' => $perPage,
            ],
            'register_filter_options' => [
                'sessions' => $sessions->map(fn (AcademicSession $session) => [
                    'id' => $session->id,
                    'name' => $session->name,
                    'code' => $session->code,
                    'status' => $session->status,
                    'is_current' => (bool) $session->is_current,
                ])->values(),
                'offerings' => $offerings,
                'disciplines' => $disciplines,
                'billing_periods' => $billingPeriods,
                'schemes' => $schemeOptions,
                'statuses' => ['PENDING','APPROVED','REJECTED','CANCELLED'],
            ],
            'register_pagination' => [
                'current_page' => $groupQuery->currentPage(),
                'last_page' => $groupQuery->lastPage(),
                'per_page' => $groupQuery->perPage(),
                'from' => $groupQuery->firstItem(),
                'to' => $groupQuery->lastItem(),
                'total' => $groupQuery->total(),
            ],
            'can' => [
                'assign' => $request->user()->hasCollegePermission('college_fee_student_benefit.assign', $college->id),
                'approve' => $request->user()->hasCollegePermission('college_fee_student_benefit.approve', $college->id),
                'reject' => $request->user()->hasCollegePermission('college_fee_student_benefit.reject', $college->id),
                'cancel' => $request->user()->hasCollegePermission('college_fee_student_benefit.cancel', $college->id),
            ],
        ]);
    }

    public function searchDemands(Request $request, College $college): JsonResponse
    {
        $this->auth($request, $college, 'college_fee_student_benefit.view');
        $validated = $request->validate(['q' => ['required','string','min:2','max:100']]);
        $q = trim($validated['q']);

        $rows = DB::table('fee_demands as d')
            ->join('admissions as ad', 'ad.id', '=', 'd.admission_id')
            ->join('college_admission_applications as a', 'a.id', '=', 'ad.college_admission_application_id')
            ->where('d.college_id', $college->id)
            ->whereIn('d.status', ['OPEN','PARTIALLY_CLEARED'])
            ->where(function ($query) use ($q) {
                $query->where('d.demand_no', 'like', '%'.$q.'%')
                    ->orWhere('ad.admission_no', 'like', '%'.$q.'%')
                    ->orWhere('a.application_no', 'like', '%'.$q.'%')
                    ->orWhere('a.candidate_name', 'like', '%'.$q.'%');
            })
            ->orderByDesc('d.id')
            ->limit(30)
            ->get([
                'd.id','d.demand_no','d.billing_period_label','d.total_amount','d.adjusted_amount','d.outstanding_amount','d.status',
                'ad.admission_no','a.application_no','a.candidate_name',
            ]);

        return response()->json(['data' => $rows]);
    }

    public function schemes(Request $request, College $college, FeeDemand $demand, FeeStudentBenefitService $service): JsonResponse
    {
        $this->auth($request, $college, 'college_fee_student_benefit.view');
        return response()->json(['data' => $service->applicableSchemes($college, $demand)]);
    }

    public function bulkSchemes(Request $request, College $college, FeeStudentBenefitService $service): JsonResponse
    {
        $this->auth($request, $college, 'college_fee_student_benefit.view');
        return response()->json(['data' => $service->bulkSchemes($college)]);
    }

    public function bulkCandidates(Request $request, College $college, FeeStudentBenefitService $service): JsonResponse
    {
        $this->auth($request, $college, 'college_fee_student_benefit.view');
        $validated = $request->validate([
            'scheme_id' => ['required','integer','exists:fee_scholarship_schemes,id'],
        ]);
        $scheme = FeeScholarshipScheme::findOrFail((int) $validated['scheme_id']);
        return response()->json(['data' => $service->bulkCandidates($college, $scheme)]);
    }

    public function bulkStore(Request $request, College $college, FeeStudentBenefitService $service): RedirectResponse
    {
        $this->auth($request, $college, 'college_fee_student_benefit.assign');
        $validated = $request->validate([
            'scheme_id' => ['required','integer','exists:fee_scholarship_schemes,id'],
            'fee_demand_ids' => ['required','array','min:1','max:500'],
            'fee_demand_ids.*' => ['required','integer', Rule::exists('fee_demands','id')->where(fn ($q) => $q->where('college_id', $college->id))],
            'application_note' => ['nullable','string','max:2000'],
        ]);

        $scheme = FeeScholarshipScheme::findOrFail((int) $validated['scheme_id']);
        $result = $service->bulkAssign(
            $college,
            $scheme,
            $validated['fee_demand_ids'],
            $request->user()->id,
            $validated['application_note'] ?? null,
        );

        $message = $result['applied'].' student benefit(s) assigned';
        if ($result['approved']) $message .= '; '.$result['approved'].' automatically sanctioned';
        if ($result['pending']) $message .= '; '.$result['pending'].' pending approval';
        if (count($result['skipped'])) $message .= '; '.count($result['skipped']).' skipped after final eligibility re-check';
        $message .= '.';

        return back()->with('toast', [
            'type' => $result['applied'] > 0 ? 'success' : 'info',
            'message' => $message,
        ]);
    }

    public function store(Request $request, College $college, FeeStudentBenefitService $service): RedirectResponse
    {
        $this->auth($request, $college, 'college_fee_student_benefit.assign');
        $validated = $request->validate([
            'fee_demand_id' => ['required','integer', Rule::exists('fee_demands','id')->where(fn ($q) => $q->where('college_id', $college->id))],
            'scheme_id' => ['required','integer','exists:fee_scholarship_schemes,id'],
            'application_note' => ['nullable','string','max:2000'],
        ]);

        $benefit = $service->assign(
            $college,
            FeeDemand::findOrFail((int) $validated['fee_demand_id']),
            FeeScholarshipScheme::findOrFail((int) $validated['scheme_id']),
            $request->user()->id,
            $validated['application_note'] ?? null,
        );

        $automatic = $benefit->status === 'APPROVED';
        return back()->with('toast', [
            'type' => 'success',
            'message' => $automatic
                ? $benefit->scheme_name_snapshot.' automatically sanctioned. Fee Demand adjusted by ₹'.number_format((float) $benefit->sanctioned_amount, 2).'.'
                : $benefit->scheme_name_snapshot.' assigned and sent for approval.',
        ]);
    }

    public function approve(Request $request, College $college, FeeStudentBenefit $benefit, FeeStudentBenefitService $service): RedirectResponse
    {
        $this->auth($request, $college, 'college_fee_student_benefit.approve');
        $validated = $request->validate([
            'sanctioned_amount' => ['required','numeric','gt:0','max:999999999.99'],
            'decision_note' => ['nullable','string','max:2000'],
            'installment_adjustment_mode' => ['nullable', Rule::in(['PROPORTIONAL','NEXT_UNPAID_FIRST','CUSTOM'])],
            'custom_installments' => ['nullable','array'],
            'custom_installments.*' => ['array'],
            'custom_installments.*.*' => ['numeric','min:0','max:999999999.99'],
        ]);
        $approved = $service->approve(
            $college,
            $benefit,
            (float) $validated['sanctioned_amount'],
            $request->user()->id,
            $validated['decision_note'] ?? null,
            $validated['installment_adjustment_mode'] ?? 'PROPORTIONAL',
            $validated['custom_installments'] ?? [],
            $request->ip(),
        );
        return back()->with('toast', [
            'type' => 'success',
            'message' => $approved->scheme_name_snapshot.' approved for ₹'.number_format((float) $approved->sanctioned_amount, 2).'. Fee Demand outstanding updated.',
        ]);
    }

    public function reject(Request $request, College $college, FeeStudentBenefit $benefit, FeeStudentBenefitService $service): RedirectResponse
    {
        $this->auth($request, $college, 'college_fee_student_benefit.reject');
        $validated = $request->validate(['decision_note' => ['required','string','max:2000']]);
        $service->reject($college, $benefit, $request->user()->id, $validated['decision_note']);
        return back()->with('toast', ['type'=>'info','message'=>'Student benefit application rejected. No Fee Demand adjustment was posted.']);
    }

    public function cancel(Request $request, College $college, FeeStudentBenefit $benefit, FeeStudentBenefitService $service): RedirectResponse
    {
        $this->auth($request, $college, 'college_fee_student_benefit.cancel');
        $validated = $request->validate(['reason' => ['required','string','max:2000']]);
        $reversed = $service->cancel($college, $benefit, $request->user()->id, $validated['reason']);
        return back()->with('toast', [
            'type' => 'success',
            'message' => $reversed
                ? 'Student benefit removed. The approved Fee Demand adjustment was reversed and outstanding recalculated.'
                : 'Student benefit removed before sanction. No Fee Demand adjustment was posted.',
        ]);
    }

    private function auth(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
