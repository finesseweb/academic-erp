<?php

namespace App\Services;

use App\Models\College;
use App\Models\FeeAdjustment;
use App\Models\FeeDemand;
use App\Models\FeePayment;
use App\Models\FeePaymentRefund;
use App\Models\FeePaymentRefundAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeeAdjustmentRefundService
{
    public function __construct(private FeeInstallmentAdjustmentService $installments) {}
    public function postAdjustment(College $college, FeeDemand $demand, array $data, int $actorId, ?string $ip=null): FeeAdjustment
    {
        abort_unless((int)$demand->college_id===(int)$college->id,404);
        return DB::transaction(function() use($college,$demand,$data,$actorId,$ip){
            $locked=FeeDemand::query()->whereKey($demand->id)->lockForUpdate()->firstOrFail();
            if($locked->status==='CANCELLED') throw ValidationException::withMessages(['demand_id'=>'Cancelled Fee Demand cannot be adjusted.']);
            $item=DB::table('fee_demand_items')->where('id',(int)$data['fee_demand_item_id'])->where('fee_demand_id',$locked->id)->lockForUpdate()->first();
            if(!$item) throw ValidationException::withMessages(['fee_demand_item_id'=>'Selected Fee Demand Item does not belong to this demand.']);
            $amount=round((float)$data['amount'],2); $direction=$data['direction'];
            if($direction==='CREDIT') {
                $open=$this->itemPrincipalOpen((int)$item->id);
                if($amount>$open+0.009) throw ValidationException::withMessages(['amount'=>'Credit adjustment exceeds this Fee Head current principal outstanding of '.number_format($open,2,'.','').'.']);
            }
            $adj=FeeAdjustment::create([
                'university_id'=>$college->university_id,'college_id'=>$college->id,'admission_id'=>$locked->admission_id,
                'academic_session_id'=>$locked->academic_session_id,'fee_demand_id'=>$locked->id,'fee_demand_item_id'=>$item->id,
                'adjustment_no'=>$this->nextNo('ADJ',$college->id,'fee_adjustments','adjustment_no'),'adjustment_date'=>$data['adjustment_date'],
                'direction'=>$direction,'amount'=>$amount,'reason_code'=>$data['reason_code'],'reason'=>trim($data['reason']),
                'status'=>'POSTED','posted_by'=>$actorId,
            ]);
            $this->installments->rebalanceForGenericAdjustment((int)$item->id,$actorId,$ip);
            $this->recalculateDemand($locked->id);
            $this->audit($actorId,'FEE_ADJUSTMENT_POSTED','fee_adjustment',$adj->id,['adjustment_no'=>$adj->adjustment_no,'direction'=>$direction,'amount'=>$amount],$ip);
            return $adj;
        });
    }

    public function reverseAdjustment(College $college, FeeAdjustment $adjustment, string $reason, int $actorId, ?string $ip=null): FeeAdjustment
    {
        abort_unless((int)$adjustment->college_id===(int)$college->id,404);
        return DB::transaction(function() use($adjustment,$reason,$actorId,$ip){
            $locked=FeeAdjustment::query()->whereKey($adjustment->id)->lockForUpdate()->firstOrFail();
            if($locked->status!=='POSTED') throw ValidationException::withMessages(['adjustment'=>'Only a POSTED adjustment can be reversed.']);
            $locked->update(['status'=>'REVERSED','reversed_at'=>now(),'reversed_by'=>$actorId,'reversal_reason'=>trim($reason)]);
            $this->installments->rebalanceForGenericAdjustment((int)$locked->fee_demand_item_id,$actorId,$ip);
            $this->recalculateDemand((int)$locked->fee_demand_id);
            $this->audit($actorId,'FEE_ADJUSTMENT_REVERSED','fee_adjustment',$locked->id,['reason'=>$reason],$ip);
            return $locked->fresh();
        });
    }

    public function reversePayment(College $college, FeePayment $payment, string $reason, int $actorId, ?string $ip=null): FeePayment
    {
        abort_unless((int)$payment->college_id===(int)$college->id,404);
        return DB::transaction(function() use($payment,$reason,$actorId,$ip){
            $locked=FeePayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if($locked->status!=='POSTED') throw ValidationException::withMessages(['payment'=>'Only a POSTED payment can be reversed.']);
            if(DB::table('fee_payment_refunds')->where('fee_payment_id',$locked->id)->where('status','POSTED')->exists())
                throw ValidationException::withMessages(['payment'=>'A payment with a posted refund cannot be fully reversed. Reverse/correct the refund first through controlled maintenance.']);
            $allocs=DB::table('fee_payment_allocations')->where('fee_payment_id',$locked->id)->lockForUpdate()->get();
            foreach($allocs->where('source_type','INSTALLMENT') as $a) if($a->fee_installment_schedule_id)
                DB::table('fee_installment_schedules')->where('id',$a->fee_installment_schedule_id)->decrement('paid_amount',(float)$a->amount,['updated_at'=>now()]);
            $locked->update(['status'=>'REVERSED','reversed_at'=>now(),'reversed_by'=>$actorId,'reversal_reason'=>trim($reason)]);
            foreach($allocs->pluck('fee_demand_id')->unique() as $id) $this->recalculateDemand((int)$id);
            $this->audit($actorId,'FEE_PAYMENT_REVERSED','fee_payment',$locked->id,['receipt_no'=>$locked->receipt_no,'amount'=>$locked->amount,'reason'=>$reason],$ip);
            return $locked->fresh();
        });
    }

    public function refundPayment(College $college, FeePayment $payment, array $data, int $actorId, ?string $ip=null): FeePaymentRefund
    {
        abort_unless((int)$payment->college_id===(int)$college->id,404);
        return DB::transaction(function() use($college,$payment,$data,$actorId,$ip){
            $locked=FeePayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if($locked->status!=='POSTED') throw ValidationException::withMessages(['payment'=>'Only a POSTED payment can be refunded.']);
            $amount=round((float)$data['amount'],2);
            $allocs=DB::table('fee_payment_allocations as a')->join('fee_demand_items as i','i.id','=','a.fee_demand_item_id')
                ->where('a.fee_payment_id',$locked->id)->where('i.is_refundable',1)->orderByDesc('a.sequence_no')->lockForUpdate()
                ->get(['a.*','i.is_refundable']);
            $already=DB::table('fee_payment_refund_allocations as ra')->join('fee_payment_refunds as r','r.id','=','ra.fee_payment_refund_id')
                ->where('r.fee_payment_id',$locked->id)->where('r.status','POSTED')->groupBy('ra.fee_payment_allocation_id')
                ->pluck(DB::raw('SUM(ra.amount)'),'ra.fee_payment_allocation_id');
            $available=round((float)$allocs->sum(fn($a)=>max((float)$a->amount-(float)($already[$a->id]??0),0)),2);
            if($amount>$available+0.009) throw ValidationException::withMessages(['amount'=>'Refund exceeds refundable paid balance of '.number_format($available,2,'.','').'. Non-refundable Fee Heads are excluded.']);
            $refund=FeePaymentRefund::create([
                'university_id'=>$college->university_id,'college_id'=>$college->id,'admission_id'=>$locked->admission_id,'academic_session_id'=>$locked->academic_session_id,
                'fee_payment_id'=>$locked->id,'refund_no'=>$this->nextNo('REF',$college->id,'fee_payment_refunds','refund_no'),'refund_date'=>$data['refund_date'],
                'amount'=>$amount,'refund_mode'=>$data['refund_mode'],'reference_no'=>filled($data['reference_no']??null)?trim($data['reference_no']):null,
                'reason'=>trim($data['reason']),'status'=>'POSTED','refunded_by'=>$actorId,
            ]);
            $remaining=$amount;$seq=1;$demandIds=[];
            foreach($allocs as $a){
                if($remaining<=0.009)break;
                $open=max(round((float)$a->amount-(float)($already[$a->id]??0),2),0); if($open<=0)continue;
                $share=round(min($remaining,$open),2);
                FeePaymentRefundAllocation::create(['fee_payment_refund_id'=>$refund->id,'fee_payment_allocation_id'=>$a->id,'fee_demand_id'=>$a->fee_demand_id,'fee_demand_item_id'=>$a->fee_demand_item_id,'fee_installment_schedule_id'=>$a->fee_installment_schedule_id,'fee_late_fine_charge_id'=>$a->fee_late_fine_charge_id,'amount'=>$share,'sequence_no'=>$seq++]);
                if($a->source_type==='INSTALLMENT' && $a->fee_installment_schedule_id) DB::table('fee_installment_schedules')->where('id',$a->fee_installment_schedule_id)->decrement('paid_amount',$share,['updated_at'=>now()]);
                $demandIds[]=(int)$a->fee_demand_id;$remaining=round($remaining-$share,2);
            }
            if($remaining>0.009) throw ValidationException::withMessages(['amount'=>'Refund could not be fully allocated. Nothing was posted.']);
            foreach(array_unique($demandIds) as $id)$this->recalculateDemand($id);
            $this->audit($actorId,'FEE_PAYMENT_REFUNDED','fee_payment_refund',$refund->id,['refund_no'=>$refund->refund_no,'receipt_no'=>$locked->receipt_no,'amount'=>$amount],$ip);
            return $refund->fresh('allocations');
        });
    }

    public function recalculateDemand(int $demandId): void
    {
        $d=DB::table('fee_demands')->where('id',$demandId)->lockForUpdate()->first(); if(!$d)return;
        $benefits=(float)DB::table('fee_student_benefits')->where('fee_demand_id',$demandId)->where('status','APPROVED')->sum('sanctioned_amount');
        $credit=(float)DB::table('fee_adjustments')->where('fee_demand_id',$demandId)->where('status','POSTED')->where('direction','CREDIT')->sum('amount');
        $debit=(float)DB::table('fee_adjustments')->where('fee_demand_id',$demandId)->where('status','POSTED')->where('direction','DEBIT')->sum('amount');
        $paid=(float)DB::table('fee_payment_allocations as a')->join('fee_payments as p','p.id','=','a.fee_payment_id')->where('a.fee_demand_id',$demandId)->where('p.status','POSTED')->whereIn('a.source_type',['DEMAND_ITEM','INSTALLMENT'])->sum('a.amount');
        $refunded=(float)DB::table('fee_payment_refund_allocations as ra')->join('fee_payment_refunds as r','r.id','=','ra.fee_payment_refund_id')->join('fee_payment_allocations as a','a.id','=','ra.fee_payment_allocation_id')->where('ra.fee_demand_id',$demandId)->where('r.status','POSTED')->whereIn('a.source_type',['DEMAND_ITEM','INSTALLMENT'])->sum('ra.amount');
        $netPaid=round($paid-$refunded,2); $netAdjusted=round($benefits+$credit-$debit,2);
        $out=max(round((float)$d->total_amount-$netPaid-$netAdjusted,2),0);
        $status=$out<=0?'CLEARED':(($netPaid!=0||$netAdjusted!=0)?'PARTIALLY_CLEARED':'OPEN');
        DB::table('fee_demands')->where('id',$demandId)->update(['paid_amount'=>$netPaid,'adjusted_amount'=>$netAdjusted,'outstanding_amount'=>$out,'status'=>$status,'updated_at'=>now()]);
    }

    private function itemPrincipalOpen(int $itemId): float
    {
        $item=DB::table('fee_demand_items')->where('id',$itemId)->first(); if(!$item)return 0;
        $benefit=(float)DB::table('fee_student_benefit_items as bi')->join('fee_student_benefits as b','b.id','=','bi.fee_student_benefit_id')->where('bi.fee_demand_item_id',$itemId)->where('b.status','APPROVED')->sum('bi.sanctioned_amount');
        $credit=(float)DB::table('fee_adjustments')->where('fee_demand_item_id',$itemId)->where('status','POSTED')->where('direction','CREDIT')->sum('amount');
        $debit=(float)DB::table('fee_adjustments')->where('fee_demand_item_id',$itemId)->where('status','POSTED')->where('direction','DEBIT')->sum('amount');
        $paid=(float)DB::table('fee_payment_allocations as a')->join('fee_payments as p','p.id','=','a.fee_payment_id')->where('a.fee_demand_item_id',$itemId)->where('p.status','POSTED')->whereIn('a.source_type',['DEMAND_ITEM','INSTALLMENT'])->sum('a.amount');
        $refund=(float)DB::table('fee_payment_refund_allocations as ra')->join('fee_payment_refunds as r','r.id','=','ra.fee_payment_refund_id')->join('fee_payment_allocations as a','a.id','=','ra.fee_payment_allocation_id')->where('ra.fee_demand_item_id',$itemId)->where('r.status','POSTED')->whereIn('a.source_type',['DEMAND_ITEM','INSTALLMENT'])->sum('ra.amount');
        return max(round((float)$item->amount+$debit-$benefit-$credit-$paid+$refund,2),0);
    }
    private function nextNo(string $prefix,int $collegeId,string $table,string $column): string { $base=$prefix.'-'.str_pad((string)$collegeId,4,'0',STR_PAD_LEFT).'-'.now()->format('Ymd').'-';$last=DB::table($table)->where($column,'like',$base.'%')->lockForUpdate()->orderByDesc('id')->value($column);$n=$last?(int)substr($last,-5)+1:1;return $base.str_pad((string)$n,5,'0',STR_PAD_LEFT); }
    private function audit(int $actor,string $event,string $type,int $id,array $after,?string $ip): void { DB::table('audit_logs')->insert(['actor_user_id'=>$actor,'event'=>$event,'resource_type'=>$type,'resource_id'=>$id,'before'=>null,'after'=>json_encode($after),'ip_address'=>$ip,'created_at'=>now()]); }
}
