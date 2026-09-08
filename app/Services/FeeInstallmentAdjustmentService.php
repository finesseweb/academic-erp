<?php

namespace App\Services;

use App\Models\FeeInstallmentSchedule;
use App\Models\FeeStudentBenefit;
use App\Models\FeeStudentBenefitItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeeInstallmentAdjustmentService
{
    public const MODES = ['PROPORTIONAL', 'NEXT_UNPAID_FIRST', 'CUSTOM'];

    public function context(FeeStudentBenefit $benefit): array
    {
        $benefit->loadMissing(['items.demandItem.installmentSchedules']);
        $items = [];
        foreach ($benefit->items as $benefitItem) {
            $demandItem = $benefitItem->demandItem;
            if (! $demandItem || ! $demandItem->installment_allowed) continue;
            $schedules = $demandItem->installmentSchedules->where('status', 'ACTIVE')->sortBy('installment_no')->values();
            if ($schedules->isEmpty()) continue;
            $items[] = [
                'fee_demand_item_id' => $demandItem->id,
                'fee_head_name' => $demandItem->fee_head_name,
                'gross_amount' => (float) $demandItem->amount,
                'benefit_calculated_amount' => (float) $benefitItem->calculated_amount,
                'benefit_eligible_amount' => (float) $benefitItem->eligible_amount,
                'current_net_payable' => $this->netPayable($demandItem->id, (float) $demandItem->amount),
                'schedules' => $schedules->map(fn ($s) => [
                    'id' => $s->id,
                    'installment_no' => $s->installment_no,
                    'amount' => (float) $s->amount,
                    'paid_amount' => (float) ($s->paid_amount ?? 0),
                    'due_date' => $s->due_date?->format('Y-m-d'),
                    'allocation_percentage' => $s->allocation_percentage !== null ? (float) $s->allocation_percentage : null,
                ])->all(),
            ];
        }
        return [
            'has_active_installments' => count($items) > 0,
            'benefit_calculated_total' => (float) $benefit->items->sum(fn ($i) => (float) $i->calculated_amount),
            'default_mode' => 'PROPORTIONAL',
            'modes' => self::MODES,
            'items' => $items,
        ];
    }

    public function applyApproval(FeeStudentBenefit $benefit, string $mode, array $customAmounts, int $actorId, ?string $ip = null): void
    {
        $mode = strtoupper($mode ?: 'PROPORTIONAL');
        if (! in_array($mode, self::MODES, true)) {
            throw ValidationException::withMessages(['installment_adjustment_mode' => 'Invalid installment adjustment mode.']);
        }

        $benefit->loadMissing(['items.demandItem.installmentSchedules']);
        $snapshots = [];
        foreach ($benefit->items as $benefitItem) {
            if ((float) $benefitItem->sanctioned_amount <= 0) continue;
            $demandItem = $benefitItem->demandItem;
            if (! $demandItem || ! $demandItem->installment_allowed) continue;
            $active = FeeInstallmentSchedule::query()->where('fee_demand_item_id', $demandItem->id)->where('status', 'ACTIVE')->orderBy('installment_no')->lockForUpdate()->get();
            if ($active->isEmpty()) continue;

            $before = $this->rows($active);
            $newNet = $this->netPayable($demandItem->id, (float) $demandItem->amount);
            $newAmounts = match ($mode) {
                'NEXT_UNPAID_FIRST' => $this->nextUnpaidFirst($active, $newNet),
                'CUSTOM' => $this->custom($active, $newNet, $customAmounts[(string) $demandItem->id] ?? $customAmounts[$demandItem->id] ?? []),
                default => $this->proportional($active, $newNet),
            };
            $this->persist($active, $newAmounts);
            $after = $this->rows(FeeInstallmentSchedule::query()->whereIn('id', $active->pluck('id'))->orderBy('installment_no')->get());
            $snapshots[] = ['fee_demand_item_id' => $demandItem->id, 'fee_head_name' => $demandItem->fee_head_name, 'net_payable' => $newNet, 'before' => $before, 'after' => $after];
            $this->audit($actorId, 'FEE_INSTALLMENT_BENEFIT_ADJUSTED', $demandItem->id, $before, ['benefit_id'=>$benefit->id,'mode'=>$mode,'rows'=>$after], $ip);
        }

        $benefit->update([
            'installment_adjustment_mode' => count($snapshots) ? $mode : null,
            'installment_adjustment_snapshot' => count($snapshots) ? ['approval'=>$snapshots] : null,
        ]);
    }

    public function applyCancellation(FeeStudentBenefit $benefit, int $actorId, ?string $ip = null): void
    {
        $snapshot = (array) ($benefit->installment_adjustment_snapshot ?? []);
        $approval = collect($snapshot['approval'] ?? [])->keyBy('fee_demand_item_id');
        $reversal = [];
        $benefit->loadMissing(['items.demandItem']);
        foreach ($benefit->items as $benefitItem) {
            $demandItem = $benefitItem->demandItem;
            if (! $demandItem || ! $demandItem->installment_allowed) continue;
            $active = FeeInstallmentSchedule::query()->where('fee_demand_item_id',$demandItem->id)->where('status','ACTIVE')->orderBy('installment_no')->lockForUpdate()->get();
            if ($active->isEmpty()) continue;
            $before = $this->rows($active);
            $targetNet = $this->netPayable($demandItem->id, (float)$demandItem->amount);
            $original = $approval->get($demandItem->id);
            $restored = false;
            if ($original && $this->sameAmounts($before, $original['after'] ?? [])) {
                $amounts = collect($original['before'] ?? [])->pluck('amount','id')->map(fn($v)=>(float)$v)->all();
                if (abs(array_sum($amounts)-$targetNet) <= 0.01) {
                    $this->persist($active, $amounts);
                    $restored = true;
                }
            }
            if (! $restored) $this->persist($active, $this->proportional($active, $targetNet));
            $after = $this->rows(FeeInstallmentSchedule::query()->whereIn('id', $active->pluck('id'))->orderBy('installment_no')->get());
            $reversal[]=['fee_demand_item_id'=>$demandItem->id,'restored_exact_snapshot'=>$restored,'net_payable'=>$targetNet,'before'=>$before,'after'=>$after];
            $this->audit($actorId,'FEE_INSTALLMENT_BENEFIT_REVERSAL',$demandItem->id,$before,['benefit_id'=>$benefit->id,'restored_exact_snapshot'=>$restored,'rows'=>$after],$ip);
        }
        if ($reversal) {
            $snapshot['reversal']=$reversal;
            $benefit->update(['installment_adjustment_snapshot'=>$snapshot]);
        }
    }

    private function netPayable(int $demandItemId, float $gross): float
    {
        $benefit = (float) DB::table('fee_student_benefit_items as bi')
            ->join('fee_student_benefits as b','b.id','=','bi.fee_student_benefit_id')
            ->where('b.status','APPROVED')->where('bi.fee_demand_item_id',$demandItemId)
            ->sum('bi.sanctioned_amount');
        return max(0, round($gross-$benefit,2));
    }

    private function proportional($rows, float $targetNet): array
    {
        $weights = [];
        $weightTotal = 0.0;
        foreach ($rows as $row) {
            $w = $row->allocation_percentage !== null ? (float)$row->allocation_percentage : max((float)$row->amount, 0.01);
            $weights[$row->id]=$w; $weightTotal += $w;
        }
        $amounts=[]; $allocated=0.0; $last=$rows->last()?->id;
        foreach ($rows as $row) {
            if ($row->id === $last) $amount = round($targetNet-$allocated,2);
            else { $amount=round($targetNet*($weights[$row->id]/max($weightTotal,0.01)),2); $allocated+=$amount; }
            $paid=(float)($row->paid_amount??0);
            if ($amount < $paid) $amount=$paid;
            $amounts[$row->id]=$amount;
        }
        $sum=array_sum($amounts);
        if ($sum > $targetNet+0.01) {
            // Paid installments are immutable. Any overpaid credit is left for Adjustment/Refund processing.
            return $amounts;
        }
        if ($sum < $targetNet-0.01 && $last) $amounts[$last]=round($amounts[$last]+($targetNet-$sum),2);
        return $amounts;
    }

    private function nextUnpaidFirst($rows, float $targetNet): array
    {
        $amounts=$rows->pluck('amount','id')->map(fn($v)=>(float)$v)->all();
        $current=array_sum($amounts); $delta=round($current-$targetNet,2);
        if ($delta > 0) {
            foreach ($rows as $row) {
                $paid=(float)($row->paid_amount??0); $reducible=max(0,$amounts[$row->id]-$paid); $take=min($reducible,$delta);
                $amounts[$row->id]=round($amounts[$row->id]-$take,2); $delta=round($delta-$take,2); if($delta<=0.01)break;
            }
        } elseif ($delta < -0.01) {
            foreach ($rows as $row) { if ((float)$row->amount > (float)($row->paid_amount??0) || (float)($row->paid_amount??0)==0) { $amounts[$row->id]=round($amounts[$row->id]+abs($delta),2); break; } }
        }
        return $amounts;
    }

    private function custom($rows, float $targetNet, array $custom): array
    {
        $amounts=[];
        foreach ($rows as $row) {
            $value=$custom[(string)$row->id] ?? $custom[$row->id] ?? null;
            if ($value === null || ! is_numeric($value) || (float)$value < (float)($row->paid_amount??0)) {
                throw ValidationException::withMessages(['custom_installments'=>'Enter a valid custom amount for every installment. Amount cannot be below already-paid value.']);
            }
            $amounts[$row->id]=round((float)$value,2);
        }
        if (abs(array_sum($amounts)-$targetNet)>0.01) {
            throw ValidationException::withMessages(['custom_installments'=>'Custom installment amounts must total the post-benefit net payable amount '.number_format($targetNet,2,'.','').'.']);
        }
        return $amounts;
    }

    private function persist($rows, array $amounts): void
    {
        foreach ($rows as $row) if (array_key_exists($row->id,$amounts)) $row->update(['amount'=>number_format($amounts[$row->id],2,'.','')]);
    }

    private function rows($rows): array
    {
        return collect($rows)->map(fn($r)=>['id'=>$r->id,'installment_no'=>$r->installment_no,'amount'=>(float)$r->amount,'paid_amount'=>(float)($r->paid_amount??0),'due_date'=>$r->due_date?->format('Y-m-d')])->values()->all();
    }

    private function sameAmounts(array $current, array $snapshot): bool
    {
        $a=collect($current)->mapWithKeys(fn($r)=>[(string)$r['id']=>(float)$r['amount']]);
        $b=collect($snapshot)->mapWithKeys(fn($r)=>[(string)$r['id']=>(float)$r['amount']]);
        if($a->keys()->sort()->values()->all()!==$b->keys()->sort()->values()->all())return false;
        foreach($a as $id=>$amount)if(abs($amount-($b[$id]??-1))>0.01)return false;
        return true;
    }

    private function audit(int $actorId,string $event,int $itemId,array $before,array $after,?string $ip): void
    {
        DB::table('audit_logs')->insert(['actor_user_id'=>$actorId,'event'=>$event,'resource_type'=>'fee_demand_item','resource_id'=>$itemId,'before'=>json_encode($before),'after'=>json_encode($after),'ip_address'=>$ip,'created_at'=>now()]);
    }
}
