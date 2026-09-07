<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\FeeDemand;
use App\Models\FeeScholarshipScheme;
use App\Models\FeeStudentBenefit;
use App\Services\FeeStudentBenefitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeFeeStudentBenefitController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        $this->auth($request, $college, 'college_fee_student_benefit.view');

        $benefits = FeeStudentBenefit::query()
            ->with(['items'])
            ->where('college_id', $college->id)
            ->orderByDesc('id')
            ->limit(250)
            ->get();

        $demandIds = $benefits->pluck('fee_demand_id')->unique()->values();
        $demandContext = DB::table('fee_demands as d')
            ->leftJoin('admissions as ad', 'ad.id', '=', 'd.admission_id')
            ->leftJoin('college_admission_applications as a', 'a.id', '=', 'ad.college_admission_application_id')
            ->whereIn('d.id', $demandIds)
            ->select([
                'd.id','d.demand_no','d.total_amount','d.adjusted_amount','d.outstanding_amount','d.status as demand_status','d.billing_period_label',
                'ad.admission_no','a.application_no','a.candidate_name',
            ])
            ->get()->keyBy('id');

        return Inertia::render('college-fee-benefits/index', [
            'college' => $college->only(['id','name','code']),
            'benefits' => $benefits->map(function (FeeStudentBenefit $benefit) use ($demandContext) {
                $d = $demandContext[$benefit->fee_demand_id] ?? null;
                return [
                    'id' => $benefit->id,
                    'fee_demand_id' => $benefit->fee_demand_id,
                    'demand_no' => $d?->demand_no,
                    'demand_status' => $d?->demand_status,
                    'billing_period_label' => $d?->billing_period_label,
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
                ];
            })->values(),
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
        ]);
        $approved = $service->approve($college, $benefit, (float) $validated['sanctioned_amount'], $request->user()->id, $validated['decision_note'] ?? null);
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
        $service->cancel($college, $benefit, $request->user()->id, $validated['reason']);
        return back()->with('toast', ['type'=>'success','message'=>'Student benefit record cancelled.']);
    }

    private function auth(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
