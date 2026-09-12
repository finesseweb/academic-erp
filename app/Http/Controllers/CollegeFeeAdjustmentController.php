<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Models\FeeAdjustment;
use App\Models\FeeDemand;
use App\Models\FeePayment;
use App\Services\FeeAdjustmentRefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeFeeAdjustmentController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        $this->auth($request, $college, 'college_fee_adjustment.view');

        $sessions = AcademicSession::query()
            ->where('university_id', $college->university_id)
            ->where('status', 'ACTIVE')
            ->orderByDesc('is_current')
            ->orderByDesc('starts_on')
            ->get(['id', 'name', 'code', 'is_current']);

        $current = (int) ($sessions->firstWhere('is_current', true)?->id ?? $sessions->first()?->id ?? 0);
        $sessionId = (int) $request->query('session_id', $current);
        $offeringId = (int) $request->query('offering_id', 0);
        $q = trim((string) $request->query('q', ''));
        $perPage = (int) $request->query('per_page', 25);
        if (! in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }

        $offerings = CollegeProgramOffering::query()
            ->with('programTemplate:id,name,code')
            ->where('college_id', $college->id)
            ->when($sessionId > 0, fn ($query) => $query->where('academic_session_id', $sessionId))
            ->get(['id', 'program_template_id', 'academic_session_id', 'status'])
            ->sortBy(fn ($offering) => mb_strtolower((string) ($offering->programTemplate?->name ?? '')))
            ->values()
            ->map(fn ($offering) => [
                'id' => (int) $offering->id,
                'academic_session_id' => (int) $offering->academic_session_id,
                'program_name' => $offering->programTemplate?->name,
                'program_code' => $offering->programTemplate?->code,
                'status' => $offering->status,
            ]);

        if ($offeringId > 0 && ! $offerings->contains(fn ($offering) => (int) $offering['id'] === $offeringId)) {
            $offeringId = 0;
        }

        $demands = FeeDemand::query()
            ->with([
                'admission.application:id,candidate_name,application_no',
                'items:id,fee_demand_id,fee_head_name,fee_head_code,amount,is_refundable',
            ])
            ->where('college_id', $college->id)
            ->where('status', '!=', 'CANCELLED')
            ->when($sessionId > 0, fn ($query) => $query->where('academic_session_id', $sessionId))
            ->when($offeringId > 0, fn ($query) => $query->where('college_program_offering_id', $offeringId))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($scope) use ($q) {
                    $scope->where('demand_no', 'like', '%'.$q.'%')
                        ->orWhereHas('admission', fn ($admission) => $admission
                            ->where('admission_no', 'like', '%'.$q.'%')
                            ->orWhereHas('application', fn ($application) => $application
                                ->where('candidate_name', 'like', '%'.$q.'%')
                                ->orWhere('application_no', 'like', '%'.$q.'%')));
                });
            })
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn ($demand) => [
                'id' => $demand->id,
                'demand_no' => $demand->demand_no,
                'admission_no' => $demand->admission?->admission_no,
                'candidate_name' => $demand->admission?->application?->candidate_name,
                'application_no' => $demand->admission?->application?->application_no,
                'currency' => $demand->currency,
                'total_amount' => $demand->total_amount,
                'paid_amount' => $demand->paid_amount,
                'adjusted_amount' => $demand->adjusted_amount,
                'outstanding_amount' => $demand->outstanding_amount,
                'items' => $demand->items->map(fn ($item) => [
                    'id' => $item->id,
                    'fee_head_name' => $item->fee_head_name,
                    'fee_head_code' => $item->fee_head_code,
                    'amount' => $item->amount,
                    'is_refundable' => (bool) $item->is_refundable,
                ])->values(),
            ]);

        /*
         * ADR 190 UI scalability follow-up:
         * paginate admissions/students, not individual receipts. A student can own many receipts;
         * keeping them together prevents the register from becoming a very long flat transaction list.
         */
        $studentPage = FeePayment::query()
            ->select('admission_id')
            ->where('college_id', $college->id)
            ->where('status', 'POSTED')
            ->when($sessionId > 0, fn ($query) => $query->where('academic_session_id', $sessionId))
            ->when($offeringId > 0, fn ($query) => $query->whereHas('admission.intake', fn ($intake) => $intake->where('college_program_offering_id', $offeringId)))
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($scope) use ($q) {
                    $scope->where('receipt_no', 'like', '%'.$q.'%')
                        ->orWhere('reference_no', 'like', '%'.$q.'%')
                        ->orWhereHas('admission', fn ($admission) => $admission
                            ->where('admission_no', 'like', '%'.$q.'%')
                            ->orWhereHas('application', fn ($application) => $application
                                ->where('candidate_name', 'like', '%'.$q.'%')
                                ->orWhere('application_no', 'like', '%'.$q.'%')))
                        ->orWhereExists(function ($subquery) use ($q) {
                            $subquery->selectRaw('1')
                                ->from('fee_payment_allocations as search_allocation')
                                ->join('fee_demands as search_demand', 'search_demand.id', '=', 'search_allocation.fee_demand_id')
                                ->whereColumn('search_allocation.fee_payment_id', 'fee_payments.id')
                                ->where('search_demand.demand_no', 'like', '%'.$q.'%');
                        });
                });
            })
            ->groupBy('admission_id')
            ->orderByDesc('admission_id')
            ->paginate($perPage, ['admission_id'], 'payment_page')
            ->withQueryString();

        $admissionIds = $studentPage->getCollection()
            ->pluck('admission_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $studentPayments = collect();
        if ($admissionIds !== []) {
            $paymentRows = FeePayment::query()
                ->with([
                    'admission.application:id,candidate_name,application_no',
                    'admission.intake.offering.programTemplate:id,name,code',
                ])
                ->where('college_id', $college->id)
                ->where('status', 'POSTED')
                ->whereIn('admission_id', $admissionIds)
                ->when($sessionId > 0, fn ($query) => $query->where('academic_session_id', $sessionId))
                ->when($offeringId > 0, fn ($query) => $query->whereHas('admission.intake', fn ($intake) => $intake->where('college_program_offering_id', $offeringId)))
                ->orderByDesc('payment_date')
                ->orderByDesc('id')
                ->get();

            $paymentIds = $paymentRows->pluck('id')->map(fn ($id) => (int) $id)->all();

            $refundableByPayment = DB::table('fee_payment_allocations as allocation')
                ->join('fee_demand_items as item', 'item.id', '=', 'allocation.fee_demand_item_id')
                ->whereIn('allocation.fee_payment_id', $paymentIds)
                ->where('item.is_refundable', 1)
                ->groupBy('allocation.fee_payment_id')
                ->selectRaw('allocation.fee_payment_id, SUM(allocation.amount) as total')
                ->pluck('total', 'allocation.fee_payment_id');

            $refundedByPayment = DB::table('fee_payment_refund_allocations as refund_allocation')
                ->join('fee_payment_refunds as refund', 'refund.id', '=', 'refund_allocation.fee_payment_refund_id')
                ->whereIn('refund.fee_payment_id', $paymentIds)
                ->where('refund.status', 'POSTED')
                ->groupBy('refund.fee_payment_id')
                ->selectRaw('refund.fee_payment_id, SUM(refund_allocation.amount) as total')
                ->pluck('total', 'refund.fee_payment_id');

            $studentPayments = $paymentRows
                ->groupBy('admission_id')
                ->map(function ($rows) use ($refundableByPayment, $refundedByPayment) {
                    $first = $rows->first();
                    $offering = $first->admission?->intake?->offering;
                    $program = $offering?->programTemplate;

                    $receipts = $rows->map(function ($payment) use ($refundableByPayment, $refundedByPayment) {
                        $refundable = (float) ($refundableByPayment[$payment->id] ?? 0);
                        $refunded = (float) ($refundedByPayment[$payment->id] ?? 0);
                        $balance = max($refundable - $refunded, 0);

                        return [
                            'id' => (int) $payment->id,
                            'receipt_no' => $payment->receipt_no,
                            'payment_date' => $payment->payment_date?->format('Y-m-d'),
                            'amount' => $payment->amount,
                            'currency' => $payment->currency,
                            'payment_mode' => $payment->payment_mode,
                            'reference_no' => $payment->reference_no,
                            'refundable_balance' => number_format($balance, 2, '.', ''),
                        ];
                    })->values();

                    return [
                        'admission_id' => (int) $first->admission_id,
                        'candidate_name' => $first->admission?->application?->candidate_name,
                        'application_no' => $first->admission?->application?->application_no,
                        'admission_no' => $first->admission?->admission_no,
                        'programme_offering' => $offering ? [
                            'id' => (int) $offering->id,
                            'program_name' => $program?->name,
                            'program_code' => $program?->code,
                        ] : null,
                        'currency' => $first->currency ?: 'INR',
                        'receipt_count' => $receipts->count(),
                        'total_paid' => number_format((float) $receipts->sum(fn ($receipt) => (float) $receipt['amount']), 2, '.', ''),
                        'refundable_balance' => number_format((float) $receipts->sum(fn ($receipt) => (float) $receipt['refundable_balance']), 2, '.', ''),
                        'receipts' => $receipts,
                    ];
                });
        }

        $studentPage->setCollection(
            $studentPage->getCollection()->map(fn ($row) => $studentPayments->get($row->admission_id))->filter()->values()
        );

        $adjustments = FeeAdjustment::query()
            ->where('college_id', $college->id)
            ->when($sessionId > 0, fn ($query) => $query->where('academic_session_id', $sessionId))
            ->when($offeringId > 0, fn ($query) => $query->whereIn('fee_demand_id', FeeDemand::query()
                ->select('id')
                ->where('college_id', $college->id)
                ->where('college_program_offering_id', $offeringId)))
            ->orderByDesc('adjustment_date')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        $refunds = DB::table('fee_payment_refunds as refund')
            ->join('fee_payments as payment', 'payment.id', '=', 'refund.fee_payment_id')
            ->where('refund.college_id', $college->id)
            ->when($sessionId > 0, fn ($query) => $query->where('refund.academic_session_id', $sessionId))
            ->when($offeringId > 0, fn ($query) => $query
                ->join('admissions as refund_admission', 'refund_admission.id', '=', 'refund.admission_id')
                ->join('college_program_intakes as refund_intake', 'refund_intake.id', '=', 'refund_admission.college_program_intake_id')
                ->where('refund_intake.college_program_offering_id', $offeringId))
            ->orderByDesc('refund.refund_date')
            ->orderByDesc('refund.id')
            ->limit(100)
            ->get(['refund.*', 'payment.receipt_no']);

        return Inertia::render('college-fee-adjustments/index', [
            'college' => $college->only(['id', 'name', 'code']),
            'sessions' => $sessions,
            'offerings' => $offerings,
            'filters' => [
                'session_id' => $sessionId,
                'offering_id' => $offeringId,
                'q' => $q,
                'per_page' => $perPage,
            ],
            'demands' => $demands,
            'payment_students' => $studentPage,
            'adjustments' => $adjustments,
            'refunds' => $refunds,
            'can' => [
                'post' => $request->user()->hasCollegePermission('college_fee_adjustment.post', $college->id),
                'reverse' => $request->user()->hasCollegePermission('college_fee_adjustment.reverse', $college->id),
                'refund' => $request->user()->hasCollegePermission('college_fee_refund.post', $college->id),
            ],
        ]);
    }

    public function store(Request $request, College $college, FeeAdjustmentRefundService $service): RedirectResponse
    {
        $this->auth($request, $college, 'college_fee_adjustment.post');
        $data = $request->validate([
            'demand_id' => ['required', 'integer', Rule::exists('fee_demands', 'id')->where(fn ($query) => $query->where('college_id', $college->id)->where('status', '!=', 'CANCELLED'))],
            'fee_demand_item_id' => ['required', 'integer'],
            'adjustment_date' => ['required', 'date'],
            'direction' => ['required', Rule::in(['CREDIT', 'DEBIT'])],
            'amount' => ['required', 'numeric', 'gt:0'],
            'reason_code' => ['required', Rule::in(['CORRECTION', 'ROUNDING', 'APPROVED_RELIEF', 'OTHER'])],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        $adjustment = $service->postAdjustment($college, FeeDemand::findOrFail($data['demand_id']), $data, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Adjustment posted: '.$adjustment->adjustment_no]);
    }

    public function reverseAdjustment(Request $request, College $college, FeeAdjustment $adjustment, FeeAdjustmentRefundService $service): RedirectResponse
    {
        $this->auth($request, $college, 'college_fee_adjustment.reverse');
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $service->reverseAdjustment($college, $adjustment, $data['reason'], $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Adjustment reversed.']);
    }

    public function reversePayment(Request $request, College $college, FeePayment $payment, FeeAdjustmentRefundService $service): RedirectResponse
    {
        $this->auth($request, $college, 'college_fee_adjustment.reverse');
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        $service->reversePayment($college, $payment, $data['reason'], $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Payment receipt reversed.']);
    }

    public function refund(Request $request, College $college, FeePayment $payment, FeeAdjustmentRefundService $service): RedirectResponse
    {
        $this->auth($request, $college, 'college_fee_refund.post');
        $data = $request->validate([
            'refund_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'refund_mode' => ['required', Rule::in(['CASH', 'CARD', 'UPI', 'BANK_TRANSFER', 'CHEQUE', 'GATEWAY', 'OTHER'])],
            'reference_no' => ['nullable', 'string', 'max:120'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);
        $refund = $service->refundPayment($college, $payment, $data, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Refund posted: '.$refund->refund_no]);
    }

    private function auth(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
