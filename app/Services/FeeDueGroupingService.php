<?php

namespace App\Services;

use App\Models\FeeDemand;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Canonical payable-date grouping projection.
 *
 * This does not merge accounting liabilities. Fee Demand Items and Installment
 * Schedules remain the allocation/audit units; this service only groups payable
 * rows that share a due date for collection/display.
 */
class FeeDueGroupingService
{
    public function forDemand(FeeDemand $demand): array
    {
        $benefitByItem = $demand->studentBenefits
            ->where('status', 'APPROVED')
            ->flatMap->items
            ->groupBy('fee_demand_item_id')
            ->map(fn (Collection $rows) => (float) $rows->sum(fn ($row) => (float) ($row->sanctioned_amount ?? 0)));

        $rows = collect();
        foreach ($demand->items as $item) {
            $activeInstallments = $item->installmentSchedules->where('status', 'ACTIVE')->sortBy('installment_no')->values();
            if ($activeInstallments->isNotEmpty()) {
                // ADR 190 QA defensive reconciliation: installment rows are a collection
                // projection of the authoritative Fee Head liability.  A historical
                // rebalance defect could leave schedule rows summing above that liability;
                // never expose or collect more than the actual Fee Head principal open.
                $authoritativeOpen = $this->itemPrincipalOpen(
                    (int) $item->id,
                    (float) $item->amount,
                    (float) ($benefitByItem[$item->id] ?? 0),
                );
                $remainingOpen = $authoritativeOpen;

                foreach ($activeInstallments as $schedule) {
                    if ($remainingOpen <= 0.009) break;
                    $scheduleOpen = max(0, round((float) $schedule->amount - (float) ($schedule->paid_amount ?? 0), 2));
                    $open = min($scheduleOpen, $remainingOpen);
                    if ($open <= 0 || ! $schedule->due_date) continue;
                    $rows->push($this->row($item, $schedule->due_date->format('Y-m-d'), $open, 'INSTALLMENT', (int) $schedule->id, (int) $schedule->installment_no));
                    $remainingOpen = round($remainingOpen - $open, 2);
                }
                continue;
            }

            if (! $item->due_date) continue;
            $creditAdjustment = Schema::hasTable('fee_adjustments') ? (float) DB::table('fee_adjustments')->where('fee_demand_item_id',$item->id)->where('status','POSTED')->where('direction','CREDIT')->sum('amount') : 0.0;
            $debitAdjustment = Schema::hasTable('fee_adjustments') ? (float) DB::table('fee_adjustments')->where('fee_demand_item_id',$item->id)->where('status','POSTED')->where('direction','DEBIT')->sum('amount') : 0.0;
            $net = max(0, round((float) $item->amount + $debitAdjustment - (float) ($benefitByItem[$item->id] ?? 0) - $creditAdjustment, 2));
            $paid = $this->postedDemandItemPaid((int) $item->id);
            $open = max(0, round($net - $paid, 2));
            if ($open <= 0) continue;
            $rows->push($this->row($item, $item->due_date->format('Y-m-d'), $open, 'DEMAND_ITEM'));
        }

        return $rows->groupBy('due_date')->map(function (Collection $dueRows, string $dueDate) {
            $mandatory = (float) $dueRows->where('is_mandatory', true)->sum('open_amount');
            $optional = (float) $dueRows->where('is_mandatory', false)->sum('open_amount');
            return [
                'due_date' => $dueDate,
                'mandatory_due' => number_format($mandatory, 2, '.', ''),
                'optional_due' => number_format($optional, 2, '.', ''),
                'combined_available' => number_format($mandatory + $optional, 2, '.', ''),
                'items' => $dueRows->values()->all(),
            ];
        })->sortKeys()->values()->all();
    }

    private function itemPrincipalOpen(int $itemId, float $gross, float $benefit): float
    {
        $creditAdjustment = Schema::hasTable('fee_adjustments')
            ? (float) DB::table('fee_adjustments')->where('fee_demand_item_id', $itemId)->where('status', 'POSTED')->where('direction', 'CREDIT')->sum('amount')
            : 0.0;
        $debitAdjustment = Schema::hasTable('fee_adjustments')
            ? (float) DB::table('fee_adjustments')->where('fee_demand_item_id', $itemId)->where('status', 'POSTED')->where('direction', 'DEBIT')->sum('amount')
            : 0.0;

        $netLiability = max(0, round($gross + $debitAdjustment - $benefit - $creditAdjustment, 2));

        if (! Schema::hasTable('fee_payment_allocations') || ! Schema::hasTable('fee_payments')) {
            return $netLiability;
        }

        $paid = (float) DB::table('fee_payment_allocations as a')
            ->join('fee_payments as p', 'p.id', '=', 'a.fee_payment_id')
            ->where('a.fee_demand_item_id', $itemId)
            ->whereIn('a.source_type', ['DEMAND_ITEM', 'INSTALLMENT'])
            ->where('p.status', 'POSTED')
            ->sum('a.amount');

        $refunded = 0.0;
        if (Schema::hasTable('fee_payment_refund_allocations') && Schema::hasTable('fee_payment_refunds')) {
            $refunded = (float) DB::table('fee_payment_refund_allocations as ra')
                ->join('fee_payment_refunds as r', 'r.id', '=', 'ra.fee_payment_refund_id')
                ->where('ra.fee_demand_item_id', $itemId)
                ->where('r.status', 'POSTED')
                ->sum('ra.amount');
        }

        return max(0, round($netLiability - $paid + $refunded, 2));
    }

    private function postedDemandItemPaid(int $itemId): float
    {
        if (! Schema::hasTable('fee_payment_allocations') || ! Schema::hasTable('fee_payments')) return 0.0;
        $paid=(float) DB::table('fee_payment_allocations as a')->join('fee_payments as p','p.id','=','a.fee_payment_id')->where('a.fee_demand_item_id',$itemId)->where('a.source_type','DEMAND_ITEM')->where('p.status','POSTED')->sum('a.amount');
        $refunded=Schema::hasTable('fee_payment_refund_allocations') ? (float) DB::table('fee_payment_refund_allocations as ra')->join('fee_payment_refunds as r','r.id','=','ra.fee_payment_refund_id')->join('fee_payment_allocations as a','a.id','=','ra.fee_payment_allocation_id')->where('ra.fee_demand_item_id',$itemId)->where('a.source_type','DEMAND_ITEM')->where('r.status','POSTED')->sum('ra.amount') : 0.0;
        return round($paid-$refunded,2);
    }

    private function row($item, string $dueDate, float $open, string $source, ?int $scheduleId = null, ?int $installmentNo = null): array
    {
        return [
            'due_date' => $dueDate,
            'fee_demand_item_id' => (int) $item->id,
            'fee_head_id' => (int) $item->fee_head_id,
            'fee_head_name' => $item->fee_head_name,
            'fee_head_code' => $item->fee_head_code,
            'is_mandatory' => (bool) $item->is_mandatory,
            'source' => $source,
            'fee_installment_schedule_id' => $scheduleId,
            'installment_no' => $installmentNo,
            'open_amount' => number_format($open, 2, '.', ''),
        ];
    }
}
