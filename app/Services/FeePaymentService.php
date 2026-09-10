<?php

namespace App\Services;

use App\Models\College;
use App\Models\FeeDemand;
use App\Models\FeePayment;
use App\Models\FeePaymentAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/**
 * ADR 171 Payment Collection + Allocation.
 *
 * Accounting units are never merged. A payment may present one combined payable amount,
 * but is deterministically allocated to Fee Demand Item / Installment / Late Fine rows.
 */
class FeePaymentService
{
    public function __construct(private FeeDueGroupingService $dueGroupingService) {}

    public function collect(College $college, FeeDemand $demand, array $data, int $actorId, ?string $ip = null): FeePayment
    {
        abort_unless((int)$demand->college_id === (int)$college->id, 404);
        if ($demand->status === 'CANCELLED') {
            throw ValidationException::withMessages(['demand_id'=>'Cancelled Fee Demand cannot receive payment.']);
        }

        $amount = round((float)$data['amount'], 2);
        if ($amount <= 0) throw ValidationException::withMessages(['amount'=>'Payment amount must be greater than zero.']);

        return DB::transaction(function () use ($college,$demand,$data,$actorId,$ip,$amount) {
            $locked = FeeDemand::query()->whereKey($demand->id)->lockForUpdate()->firstOrFail();
            $locked->load([
                'items.installmentSchedules'=>fn($q)=>$q->where('status','ACTIVE'),
                'studentBenefits'=>fn($q)=>$q->where('status','APPROVED')->with('items'),
            ]);

            $candidates = $this->allocationCandidates(
                $locked,
                (bool)($data['include_optional'] ?? false),
                (bool)($data['include_late_fine'] ?? true),
                (bool)($data['include_future'] ?? false),
                (string)$data['payment_date']
            );
            $available = round((float)$candidates->sum('open_amount'), 2);
            if ($available <= 0) throw ValidationException::withMessages(['amount'=>'No selected payable balance remains on this demand.']);
            if ($amount > $available + 0.009) {
                throw ValidationException::withMessages(['amount'=>'Payment exceeds selected payable balance of '.number_format($available,2,'.','').'.']);
            }

            $payment = FeePayment::create([
                'university_id'=>$college->university_id,'college_id'=>$college->id,
                'admission_id'=>$locked->admission_id,'academic_session_id'=>$locked->academic_session_id,
                'receipt_no'=>$this->nextReceiptNo($college->id),'payment_date'=>$data['payment_date'],
                'amount'=>$amount,'currency'=>$locked->currency ?: 'INR','payment_mode'=>$data['payment_mode'],
                'reference_no'=>filled($data['reference_no'] ?? null)?trim($data['reference_no']):null,
                'status'=>'POSTED','notes'=>filled($data['notes'] ?? null)?trim($data['notes']):null,'collected_by'=>$actorId,
            ]);

            $remaining = $amount; $sequence = 1; $principalPaid = 0.0;
            foreach ($candidates as $candidate) {
                if ($remaining <= 0.009) break;
                $share = min($remaining, (float)$candidate['open_amount']);
                $share = round($share, 2);
                if ($share <= 0) continue;
                FeePaymentAllocation::create([
                    'fee_payment_id'=>$payment->id,'fee_demand_id'=>$locked->id,
                    'fee_demand_item_id'=>$candidate['fee_demand_item_id'],
                    'fee_installment_schedule_id'=>$candidate['fee_installment_schedule_id'],
                    'fee_late_fine_charge_id'=>$candidate['fee_late_fine_charge_id'],
                    'source_type'=>$candidate['source_type'],'due_date'=>$candidate['due_date'],
                    'amount'=>$share,'is_mandatory'=>$candidate['is_mandatory'],'sequence_no'=>$sequence++,
                ]);
                if ($candidate['source_type'] === 'INSTALLMENT') {
                    DB::table('fee_installment_schedules')->where('id',$candidate['fee_installment_schedule_id'])
                        ->increment('paid_amount',$share,['updated_at'=>now()]);
                    $principalPaid += $share;
                } elseif ($candidate['source_type'] === 'DEMAND_ITEM') {
                    $principalPaid += $share;
                }
                $remaining = round($remaining - $share, 2);
            }
            if ($remaining > 0.009) throw ValidationException::withMessages(['amount'=>'Payment could not be fully allocated. Nothing was posted.']);

            $newPaid = round((float)$locked->paid_amount + $principalPaid, 2);
            $outstanding = max(round((float)$locked->total_amount - $newPaid - (float)$locked->adjusted_amount, 2), 0);
            $status = $outstanding <= 0 ? 'CLEARED' : (($newPaid + (float)$locked->adjusted_amount) > 0 ? 'PARTIALLY_CLEARED' : 'OPEN');
            $locked->update(['paid_amount'=>$newPaid,'outstanding_amount'=>$outstanding,'status'=>$status]);

            DB::table('audit_logs')->insert([
                'actor_user_id'=>$actorId,'event'=>'FEE_PAYMENT_POSTED','resource_type'=>'fee_payment','resource_id'=>$payment->id,
                'before'=>null,'after'=>json_encode(['receipt_no'=>$payment->receipt_no,'amount'=>$amount,'principal_paid'=>$principalPaid,'allocations'=>$payment->allocations()->get()->toArray()]),
                'ip_address'=>$ip,'created_at'=>now(),
            ]);
            return $payment->fresh('allocations');
        });
    }

    public function outstandingFineBreakdown(FeeDemand $demand): array
    {
        if (! Schema::hasTable('fee_late_fine_charges')) return ['mandatory'=>0.0,'optional'=>0.0,'total'=>0.0];
        $totals=['mandatory'=>0.0,'optional'=>0.0];
        $charges=DB::table('fee_late_fine_charges as f')->join('fee_demand_items as i','i.id','=','f.fee_demand_item_id')
            ->where('f.fee_demand_id',$demand->id)->where('f.status','ACTIVE')->get(['f.id','f.fine_amount','i.is_mandatory']);
        foreach($charges as $charge){
            $paid=0.0;
            if(Schema::hasTable('fee_payment_allocations')&&Schema::hasTable('fee_payments')){
                $paid=(float)DB::table('fee_payment_allocations as a')->join('fee_payments as p','p.id','=','a.fee_payment_id')
                    ->where('a.fee_late_fine_charge_id',$charge->id)->where('a.source_type','LATE_FINE')->where('p.status','POSTED')->sum('a.amount');
            }
            $open=max(0,round((float)$charge->fine_amount-$paid,2));
            $totals[$charge->is_mandatory?'mandatory':'optional']+=$open;
        }
        $totals['mandatory']=round($totals['mandatory'],2);$totals['optional']=round($totals['optional'],2);$totals['total']=round($totals['mandatory']+$totals['optional'],2);
        return $totals;
    }

    public function paymentRows(College $college, array $filters)
    {
        return FeePayment::query()
            ->with(['admission.application:id,candidate_name,application_no','allocations'])
            ->where('college_id',$college->id)->where('status','POSTED')
            ->when(!empty($filters['session_id']),fn($q)=>$q->where('academic_session_id',$filters['session_id']))
            ->when(!empty($filters['q']),function($q) use($filters){$s=$filters['q'];$q->where(function($x)use($s){$x->where('receipt_no','like','%'.$s.'%')->orWhere('reference_no','like','%'.$s.'%')->orWhereHas('admission.application',fn($a)=>$a->where('candidate_name','like','%'.$s.'%')->orWhere('application_no','like','%'.$s.'%'));});})
            ->orderByDesc('payment_date')->orderByDesc('id');
    }

    private function allocationCandidates(FeeDemand $demand, bool $includeOptional, bool $includeLateFine, bool $includeFuture, string $paymentDate)
    {
        $rows = collect();
        foreach ($this->dueGroupingService->forDemand($demand) as $group) {
            foreach ($group['items'] as $item) {
                if (! $includeFuture && $item['due_date'] > $paymentDate) continue;
                if (! $item['is_mandatory'] && ! $includeOptional) continue;
                $rows->push([
                    'source_type'=>$item['source'],'due_date'=>$item['due_date'],'fee_demand_item_id'=>$item['fee_demand_item_id'],
                    'fee_installment_schedule_id'=>$item['fee_installment_schedule_id'],'fee_late_fine_charge_id'=>null,
                    'is_mandatory'=>$item['is_mandatory'],'open_amount'=>(float)$item['open_amount'],
                    'priority'=>$item['is_mandatory']?10:30,
                ]);
            }
        }

        if ($includeLateFine && Schema::hasTable('fee_late_fine_charges')) {
            $fineRows = DB::table('fee_late_fine_charges as f')
                ->join('fee_demand_items as i','i.id','=','f.fee_demand_item_id')
                ->where('f.fee_demand_id',$demand->id)->where('f.status','ACTIVE')
                ->select('f.id','f.fee_demand_item_id','f.fee_installment_schedule_id','f.due_date','f.fine_amount','i.is_mandatory')
                ->orderBy('f.due_date')->orderBy('f.id')->get();
            foreach ($fineRows as $fine) {
                if (! $includeFuture && substr((string)$fine->due_date,0,10) > $paymentDate) continue;
                if (! $fine->is_mandatory && ! $includeOptional) continue;
                $already = (float) DB::table('fee_payment_allocations as a')->join('fee_payments as p','p.id','=','a.fee_payment_id')
                    ->where('a.fee_late_fine_charge_id',$fine->id)->where('a.source_type','LATE_FINE')->where('p.status','POSTED')->sum('a.amount');
                $open=max(0,round((float)$fine->fine_amount-$already,2)); if($open<=0) continue;
                $rows->push(['source_type'=>'LATE_FINE','due_date'=>(string)$fine->due_date,'fee_demand_item_id'=>(int)$fine->fee_demand_item_id,
                    'fee_installment_schedule_id'=>(int)$fine->fee_installment_schedule_id,'fee_late_fine_charge_id'=>(int)$fine->id,
                    'is_mandatory'=>(bool)$fine->is_mandatory,'open_amount'=>$open,'priority'=>$fine->is_mandatory?20:40]);
            }
        }

        return $rows->sortBy(fn($r)=>str_pad((string)$r['priority'],3,'0',STR_PAD_LEFT).'|'.$r['due_date'].'|'.str_pad((string)$r['fee_demand_item_id'],12,'0',STR_PAD_LEFT))->values();
    }

    private function nextReceiptNo(int $collegeId): string
    {
        $prefix='RCP-'.now()->format('Ymd').'-'.$collegeId.'-';
        $last=DB::table('fee_payments')->where('college_id',$collegeId)->where('receipt_no','like',$prefix.'%')->lockForUpdate()->orderByDesc('id')->value('receipt_no');
        $seq=$last ? ((int)substr($last,strrpos($last,'-')+1)+1) : 1;
        return $prefix.str_pad((string)$seq,5,'0',STR_PAD_LEFT);
    }
}
