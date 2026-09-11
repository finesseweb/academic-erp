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
                foreach ($activeInstallments as $schedule) {
                    $open = max(0, round((float) $schedule->amount - (float) ($schedule->paid_amount ?? 0), 2));
                    if ($open <= 0 || ! $schedule->due_date) continue;
                    $rows->push($this->row($item, $schedule->due_date->format('Y-m-d'), $open, 'INSTALLMENT', (int) $schedule->id, (int) $schedule->installment_no));
                }
                continue;
            }

            if (! $item->due_date) continue;
            $net = max(0, round((float) $item->amount - (float) ($benefitByItem[$item->id] ?? 0), 2));
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

    private function postedDemandItemPaid(int $itemId): float
    {
        if (! Schema::hasTable('fee_payment_allocations') || ! Schema::hasTable('fee_payments')) return 0.0;
        return (float) DB::table('fee_payment_allocations as a')
            ->join('fee_payments as p', 'p.id', '=', 'a.fee_payment_id')
            ->where('a.fee_demand_item_id', $itemId)
            ->where('a.source_type', 'DEMAND_ITEM')
            ->where('p.status', 'POSTED')
            ->sum('a.amount');
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
