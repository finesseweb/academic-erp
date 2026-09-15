<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\College;
use App\Models\FeeDemand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * ADR 189 Student Fee Ledger.
 *
 * The ledger is a read-only projection over authoritative fee transactions.
 * It never stores or recalculates a second accounting balance.
 */
class FeeLedgerService
{
    public function studentRegister(College $college, array $filters): LengthAwarePaginator
    {
        $sessionId = (int) ($filters['session_id'] ?? 0);
        $search = trim((string) ($filters['q'] ?? ''));
        $perPage = (int) ($filters['per_page'] ?? 25);
        if (! in_array($perPage, [25, 50, 100], true)) {
            $perPage = 25;
        }

        $query = Admission::query()
            ->with([
                'application:id,candidate_name,application_no,email,phone',
                'intake.offering.programTemplate:id,name,code',
                'intake.offering.academicSession:id,name,code',
            ])
            ->where('college_id', $college->id)
            ->whereHas('feeDemands', function ($demand) use ($sessionId) {
                $demand->when($sessionId > 0, fn ($q) => $q->where('academic_session_id', $sessionId));
            })
            ->when($search !== '', function ($query) use ($search, $sessionId) {
                $query->where(function ($searchQuery) use ($search, $sessionId) {
                    $searchQuery->where('admission_no', 'like', '%'.$search.'%')
                        ->orWhereHas('application', function ($application) use ($search) {
                            $application->where('candidate_name', 'like', '%'.$search.'%')
                                ->orWhere('application_no', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%');
                        })
                        ->orWhereHas('feeDemands', function ($demand) use ($search, $sessionId) {
                            $demand->when($sessionId > 0, fn ($q) => $q->where('academic_session_id', $sessionId))
                                ->where(function ($q) use ($search) {
                                    $q->where('demand_no', 'like', '%'.$search.'%')
                                        ->orWhere('billing_period_label', 'like', '%'.$search.'%');
                                });
                        });
                });
            })
            ->withCount(['feeDemands as demand_count' => function ($demand) use ($sessionId) {
                $demand->when($sessionId > 0, fn ($q) => $q->where('academic_session_id', $sessionId));
            }])
            ->orderByDesc('id');

        $page = $query->paginate($perPage)->withQueryString();

        $page->setCollection($page->getCollection()->map(function (Admission $admission) use ($sessionId) {
            $summary = $this->summaryForAdmission($admission->id, $sessionId);
            $offering = $admission->intake?->offering;

            return [
                'admission_id' => $admission->id,
                'admission_no' => $admission->admission_no,
                'candidate_name' => $admission->application?->candidate_name,
                'application_no' => $admission->application?->application_no,
                'email' => $admission->application?->email,
                'phone' => $admission->application?->phone,
                'program' => $offering?->programTemplate?->name,
                'program_code' => $offering?->programTemplate?->code,
                'session' => $offering?->academicSession?->name,
                'demand_count' => (int) $admission->demand_count,
                'currency' => $summary['currency'],
                'debits' => $summary['debits'],
                'credits' => $summary['credits'],
                'balance' => $summary['balance'],
            ];
        }));

        return $page;
    }

    public function ledger(College $college, Admission $admission, int $sessionId = 0): array
    {
        abort_unless((int) $admission->college_id === (int) $college->id, 404);

        $admission->loadMissing([
            'application:id,candidate_name,application_no,email,phone',
            'intake.offering.programTemplate:id,name,code',
            'intake.offering.academicSession:id,name,code',
        ]);

        $demands = FeeDemand::query()
            ->with('items')
            ->where('college_id', $college->id)
            ->where('admission_id', $admission->id)
            ->when($sessionId > 0, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->orderBy('generated_at')->orderBy('id')->get();

        $demandIds = $demands->pluck('id')->map(fn ($id) => (int) $id)->all();
        $entries = collect();

        foreach ($demands as $demand) {
            foreach ($demand->items as $item) {
                $entries->push($this->entry(
                    date: $this->dateOnly($demand->generated_at ?? $demand->created_at),
                    sortAt: $this->sortAt($demand->generated_at ?? $demand->created_at, 10, $item->id),
                    type: 'DEMAND',
                    label: 'Fee Demand',
                    reference: $demand->demand_no,
                    description: trim($item->fee_head_name.($demand->billing_period_label ? ' · '.$demand->billing_period_label : '')),
                    debit: (float) $item->amount,
                    credit: 0,
                    demandId: $demand->id,
                    demandItemId: $item->id,
                    dueDate: $item->due_date?->format('Y-m-d'),
                    meta: ['fee_head_code' => $item->fee_head_code, 'purpose' => $item->purpose],
                ));
            }

            if ($demand->status === 'CANCELLED' && $demand->cancelled_at) {
                foreach ($demand->items as $item) {
                    $entries->push($this->entry(
                        date: $this->dateOnly($demand->cancelled_at),
                        sortAt: $this->sortAt($demand->cancelled_at, 50, $item->id),
                        type: 'REVERSAL',
                        label: 'Fee Demand Cancellation',
                        reference: $demand->demand_no,
                        description: trim($item->fee_head_name.' · cancelled demand'),
                        debit: 0,
                        credit: (float) $item->amount,
                        demandId: $demand->id,
                        demandItemId: $item->id,
                        dueDate: $item->due_date?->format('Y-m-d'),
                        meta: ['fee_head_code' => $item->fee_head_code, 'reason' => $demand->cancellation_reason],
                    ));
                }
            }
        }

        if ($demandIds) {
            $benefits = DB::table('fee_student_benefits as b')
                ->join('fee_student_benefit_items as bi', 'bi.fee_student_benefit_id', '=', 'b.id')
                ->join('fee_demand_items as di', 'di.id', '=', 'bi.fee_demand_item_id')
                ->whereIn('b.fee_demand_id', $demandIds)
                ->whereIn('b.status', ['APPROVED','CANCELLED'])
                ->whereNotNull('b.decided_at')
                ->whereNotNull('bi.sanctioned_amount')
                ->orderBy('b.decided_at')->orderBy('b.id')->orderBy('bi.id')
                ->get([
                    'b.id as benefit_id','b.fee_demand_id','b.scheme_name_snapshot','b.scheme_code_snapshot','b.benefit_type_snapshot',
                    'b.decided_at','b.cancelled_at','b.cancellation_reason','bi.id as benefit_item_id','bi.fee_demand_item_id','bi.sanctioned_amount','di.fee_head_name','di.fee_head_code',
                ]);

            foreach ($benefits as $benefit) {
                $entries->push($this->entry(
                    date: $this->dateOnly($benefit->decided_at),
                    sortAt: $this->sortAt($benefit->decided_at, 20, $benefit->benefit_item_id),
                    type: 'BENEFIT',
                    label: ucwords(strtolower(str_replace('_', ' ', (string) $benefit->benefit_type_snapshot))),
                    reference: $benefit->scheme_code_snapshot,
                    description: $benefit->scheme_name_snapshot.' · '.$benefit->fee_head_name,
                    debit: 0,
                    credit: (float) $benefit->sanctioned_amount,
                    demandId: (int) $benefit->fee_demand_id,
                    demandItemId: (int) $benefit->fee_demand_item_id,
                    dueDate: null,
                    meta: ['fee_head_code' => $benefit->fee_head_code, 'benefit_id' => (int) $benefit->benefit_id],
                ));

                if ($benefit->cancelled_at) {
                    $entries->push($this->entry(
                        date: $this->dateOnly($benefit->cancelled_at),
                        sortAt: $this->sortAt($benefit->cancelled_at, 50, $benefit->benefit_item_id),
                        type: 'REVERSAL',
                        label: 'Benefit Cancellation',
                        reference: $benefit->scheme_code_snapshot,
                        description: $benefit->scheme_name_snapshot.' · '.$benefit->fee_head_name,
                        debit: (float) $benefit->sanctioned_amount,
                        credit: 0,
                        demandId: (int) $benefit->fee_demand_id,
                        demandItemId: (int) $benefit->fee_demand_item_id,
                        dueDate: null,
                        meta: ['fee_head_code' => $benefit->fee_head_code, 'benefit_id' => (int) $benefit->benefit_id, 'reason' => $benefit->cancellation_reason],
                    ));
                }
            }

            $fines = DB::table('fee_late_fine_charges as f')
                ->join('fee_demand_items as di', 'di.id', '=', 'f.fee_demand_item_id')
                ->whereIn('f.fee_demand_id', $demandIds)
                ->where('f.status', 'ACTIVE')
                ->orderBy('f.calculated_as_of')->orderBy('f.id')
                ->get([
                    'f.id','f.fee_demand_id','f.fee_demand_item_id','f.fee_installment_schedule_id','f.due_date','f.calculated_as_of',
                    'f.fine_amount','di.fee_head_name','di.fee_head_code',
                ]);

            foreach ($fines as $fine) {
                $entries->push($this->entry(
                    date: $this->dateOnly($fine->calculated_as_of),
                    sortAt: $this->sortAt($fine->calculated_as_of, 30, $fine->id),
                    type: 'LATE_FINE',
                    label: 'Late Fine',
                    reference: 'FINE-'.$fine->id,
                    description: $fine->fee_head_name.' · overdue charge',
                    debit: (float) $fine->fine_amount,
                    credit: 0,
                    demandId: (int) $fine->fee_demand_id,
                    demandItemId: (int) $fine->fee_demand_item_id,
                    dueDate: $this->dateOnly($fine->due_date),
                    meta: ['fee_head_code' => $fine->fee_head_code, 'installment_id' => (int) $fine->fee_installment_schedule_id],
                ));
            }

            $allocations = DB::table('fee_payment_allocations as a')
                ->join('fee_payments as p', 'p.id', '=', 'a.fee_payment_id')
                ->join('fee_demand_items as di', 'di.id', '=', 'a.fee_demand_item_id')
                ->whereIn('a.fee_demand_id', $demandIds)
                ->whereIn('p.status', ['POSTED','REVERSED'])
                ->orderBy('p.payment_date')->orderBy('p.id')->orderBy('a.sequence_no')
                ->get([
                    'a.id','a.fee_payment_id','a.fee_demand_id','a.fee_demand_item_id','a.fee_installment_schedule_id','a.fee_late_fine_charge_id',
                    'a.source_type','a.due_date','a.amount','a.sequence_no','p.receipt_no','p.payment_date','p.created_at as payment_created_at','p.payment_mode','p.reference_no','p.status as payment_status','p.reversed_at','p.reversal_reason',
                    'di.fee_head_name','di.fee_head_code',
                ]);

            foreach ($allocations as $allocation) {
                $source = match ($allocation->source_type) {
                    'INSTALLMENT' => 'Installment payment',
                    'LATE_FINE' => 'Late Fine payment',
                    default => 'Fee payment',
                };
                $entries->push($this->entry(
                    date: $this->dateOnly($allocation->payment_date),
                    sortAt: $this->sortAtEvent($allocation->payment_date, $allocation->payment_created_at, 40, $allocation->id, (int) $allocation->sequence_no),
                    type: 'PAYMENT',
                    label: $source,
                    reference: $allocation->receipt_no,
                    description: $allocation->fee_head_name.' · '.str_replace('_', ' ', $allocation->payment_mode),
                    debit: 0,
                    credit: (float) $allocation->amount,
                    demandId: (int) $allocation->fee_demand_id,
                    demandItemId: (int) $allocation->fee_demand_item_id,
                    dueDate: $this->dateOnly($allocation->due_date),
                    meta: [
                        'fee_head_code' => $allocation->fee_head_code,
                        'payment_id' => (int) $allocation->fee_payment_id,
                        'payment_reference' => $allocation->reference_no,
                        'source_type' => $allocation->source_type,
                        'installment_id' => $allocation->fee_installment_schedule_id ? (int) $allocation->fee_installment_schedule_id : null,
                        'late_fine_charge_id' => $allocation->fee_late_fine_charge_id ? (int) $allocation->fee_late_fine_charge_id : null,
                    ],
                ));
                if ($allocation->payment_status === 'REVERSED' && $allocation->reversed_at) {
                    $entries->push($this->entry(
                        date: $this->dateOnly($allocation->reversed_at), sortAt: $this->sortAtEvent($allocation->reversed_at, $allocation->reversed_at, 60, $allocation->id, (int)$allocation->sequence_no),
                        type: 'REVERSAL', label: 'Payment Reversal', reference: $allocation->receipt_no,
                        description: $allocation->fee_head_name.' · receipt reversed', debit: (float)$allocation->amount, credit: 0,
                        demandId: (int)$allocation->fee_demand_id, demandItemId: (int)$allocation->fee_demand_item_id, dueDate: $this->dateOnly($allocation->due_date),
                        meta: ['fee_head_code'=>$allocation->fee_head_code,'payment_id'=>(int)$allocation->fee_payment_id,'reason'=>$allocation->reversal_reason],
                    ));
                }
            }

            if (\Schema::hasTable('fee_adjustments')) {
                $adjustments=DB::table('fee_adjustments as x')->join('fee_demand_items as di','di.id','=','x.fee_demand_item_id')->whereIn('x.fee_demand_id',$demandIds)->whereIn('x.status',['POSTED','REVERSED'])->orderBy('x.adjustment_date')->orderBy('x.id')->get(['x.*','di.fee_head_name','di.fee_head_code']);
                foreach($adjustments as $x){
                    $entries->push($this->entry(date:$this->dateOnly($x->adjustment_date),sortAt:$this->sortAtEvent($x->adjustment_date,$x->created_at,35,$x->id),type:'ADJUSTMENT',label:$x->direction==='CREDIT'?'Credit Adjustment':'Debit Adjustment',reference:$x->adjustment_no,description:$x->fee_head_name.' · '.$x->reason,debit:$x->direction==='DEBIT'?(float)$x->amount:0,credit:$x->direction==='CREDIT'?(float)$x->amount:0,demandId:(int)$x->fee_demand_id,demandItemId:(int)$x->fee_demand_item_id,dueDate:null,meta:['fee_head_code'=>$x->fee_head_code,'reason_code'=>$x->reason_code]));
                    if($x->status==='REVERSED'&&$x->reversed_at)$entries->push($this->entry(date:$this->dateOnly($x->reversed_at),sortAt:$this->sortAtEvent($x->reversed_at,$x->reversed_at,60,$x->id),type:'REVERSAL',label:'Adjustment Reversal',reference:$x->adjustment_no,description:$x->fee_head_name.' · '.$x->reversal_reason,debit:$x->direction==='CREDIT'?(float)$x->amount:0,credit:$x->direction==='DEBIT'?(float)$x->amount:0,demandId:(int)$x->fee_demand_id,demandItemId:(int)$x->fee_demand_item_id,dueDate:null,meta:['fee_head_code'=>$x->fee_head_code]));
                }
            }
            if (\Schema::hasTable('fee_payment_refunds')) {
                $refunds=DB::table('fee_payment_refund_allocations as ra')->join('fee_payment_refunds as r','r.id','=','ra.fee_payment_refund_id')->join('fee_payment_allocations as a','a.id','=','ra.fee_payment_allocation_id')->join('fee_demand_items as di','di.id','=','ra.fee_demand_item_id')->join('fee_payments as p','p.id','=','r.fee_payment_id')->whereIn('ra.fee_demand_id',$demandIds)->where('r.status','POSTED')->orderBy('r.refund_date')->orderBy('r.id')->orderBy('ra.sequence_no')->get(['ra.*','r.refund_no','r.refund_date','r.created_at as refund_created_at','r.refund_mode','r.reference_no as refund_reference','r.reason','p.receipt_no','a.source_type','di.fee_head_name','di.fee_head_code']);
                foreach($refunds as $r)$entries->push($this->entry(date:$this->dateOnly($r->refund_date),sortAt:$this->sortAtEvent($r->refund_date,$r->refund_created_at,55,$r->id,(int)$r->sequence_no),type:'REFUND',label:'Payment Refund',reference:$r->refund_no,description:$r->fee_head_name.' · against '.$r->receipt_no,debit:(float)$r->amount,credit:0,demandId:(int)$r->fee_demand_id,demandItemId:(int)$r->fee_demand_item_id,dueDate:null,meta:['fee_head_code'=>$r->fee_head_code,'refund_mode'=>$r->refund_mode,'refund_reference'=>$r->refund_reference,'reason'=>$r->reason]));
            }
        }

        $running = 0.0;
        $entries = $entries->sortBy([['sort_at', 'asc'], ['type', 'asc']])->values()->map(function (array $entry) use (&$running) {
            $running = round($running + (float) $entry['debit'] - (float) $entry['credit'], 2);
            $entry['balance'] = number_format($running, 2, '.', '');
            unset($entry['sort_at']);
            return $entry;
        });

        $debits = round((float) $entries->sum(fn ($row) => (float) $row['debit']), 2);
        $credits = round((float) $entries->sum(fn ($row) => (float) $row['credit']), 2);
        $currency = (string) ($demands->first()?->currency ?: 'INR');
        $offering = $admission->intake?->offering;

        return [
            'student' => [
                'admission_id' => $admission->id,
                'admission_no' => $admission->admission_no,
                'candidate_name' => $admission->application?->candidate_name,
                'application_no' => $admission->application?->application_no,
                'email' => $admission->application?->email,
                'phone' => $admission->application?->phone,
                'program' => $offering?->programTemplate?->name,
                'program_code' => $offering?->programTemplate?->code,
                'session' => $offering?->academicSession?->name,
            ],
            'summary' => [
                'currency' => $currency,
                'debits' => number_format($debits, 2, '.', ''),
                'credits' => number_format($credits, 2, '.', ''),
                'balance' => number_format(round($debits - $credits, 2), 2, '.', ''),
                'demand_count' => $demands->count(),
                'entry_count' => $entries->count(),
            ],
            'entries' => $entries->all(),
        ];
    }

    private function summaryForAdmission(int $admissionId, int $sessionId): array
    {
        $demands = DB::table('fee_demands')
            ->where('admission_id', $admissionId)
            ->where('status', '!=', 'CANCELLED')
            ->when($sessionId > 0, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->get(['id','currency','total_amount','adjusted_amount','paid_amount']);

        $demandIds = $demands->pluck('id')->map(fn ($id) => (int) $id)->all();
        $principalDebit = round((float) $demands->sum('total_amount'), 2);
        $benefitCredit = round((float) $demands->sum('adjusted_amount'), 2);
        $principalPaymentCredit = round((float) $demands->sum('paid_amount'), 2);
        $fineDebit = 0.0;
        $finePaymentCredit = 0.0;

        if ($demandIds) {
            $fineDebit = round((float) DB::table('fee_late_fine_charges')->whereIn('fee_demand_id', $demandIds)->where('status', 'ACTIVE')->sum('fine_amount'), 2);
            $finePaymentCredit = round((float) DB::table('fee_payment_allocations as a')
                ->join('fee_payments as p', 'p.id', '=', 'a.fee_payment_id')
                ->whereIn('a.fee_demand_id', $demandIds)->where('a.source_type', 'LATE_FINE')->where('p.status', 'POSTED')->sum('a.amount'), 2);
        }

        $debits = round($principalDebit + $fineDebit, 2);
        $credits = round($benefitCredit + $principalPaymentCredit + $finePaymentCredit, 2);

        return [
            'currency' => (string) ($demands->first()?->currency ?: 'INR'),
            'debits' => number_format($debits, 2, '.', ''),
            'credits' => number_format($credits, 2, '.', ''),
            'balance' => number_format(max(round($debits - $credits, 2), 0), 2, '.', ''),
        ];
    }

    private function entry(string $date, string $sortAt, string $type, string $label, ?string $reference, string $description, float $debit, float $credit, int $demandId, int $demandItemId, ?string $dueDate, array $meta = []): array
    {
        return [
            'date' => $date,
            'sort_at' => $sortAt,
            'type' => $type,
            'label' => $label,
            'reference' => $reference,
            'description' => $description,
            'debit' => number_format(round($debit, 2), 2, '.', ''),
            'credit' => number_format(round($credit, 2), 2, '.', ''),
            'balance' => '0.00',
            'fee_demand_id' => $demandId,
            'fee_demand_item_id' => $demandItemId,
            'due_date' => $dueDate,
            'meta' => $meta,
        ];
    }

    private function dateOnly(mixed $value): string
    {
        if ($value === null || $value === '') return now()->toDateString();
        return substr((string) $value, 0, 10);
    }

    /**
     * Build a deterministic ledger sort key for transactions whose business date
     * may contain no time component. The event timestamp preserves the real
     * posting sequence for multiple adjustments/payments/refunds/reversals on
     * the same calendar date. Priority/id/sequence remain stable fallbacks.
     */
    private function sortAtEvent(mixed $businessDate, mixed $eventAt, int $priority, int $id, int $sequence = 0): string
    {
        $date = $this->dateOnly($businessDate);
        $time = '12:00:00.000000';

        if ($eventAt !== null && $eventAt !== '') {
            try {
                $time = \Illuminate\Support\Carbon::parse($eventAt)->format('H:i:s.u');
            } catch (\Throwable) {
                // Keep the deterministic noon fallback for legacy/incomplete rows.
            }
        }

        return $date.'|'.$time.'|'.str_pad((string) $priority, 3, '0', STR_PAD_LEFT).'|'.str_pad((string) $id, 12, '0', STR_PAD_LEFT).'|'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    private function sortAt(mixed $value, int $priority, int $id, int $sequence = 0): string
    {
        $date = $this->dateOnly($value);
        return $date.'|'.str_pad((string) $priority, 3, '0', STR_PAD_LEFT).'|'.str_pad((string) $id, 12, '0', STR_PAD_LEFT).'|'.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }
}
