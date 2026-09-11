<?php

namespace App\Services;

use App\Models\College;
use App\Models\FeeLateFineCharge;
use App\Models\FeeLateFineRule;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class FeeLateFineService
{
    public function save(?FeeLateFineRule $rule, College $college, array $data, int $actorId): FeeLateFineRule
    {
        $offering = DB::table('college_program_offerings')
            ->where('id', $data['college_program_offering_id'])
            ->where('college_id', $college->id)
            ->where('status', 'ACTIVE')
            ->first();
        if (! $offering) {
            throw ValidationException::withMessages(['college_program_offering_id' => 'Select an ACTIVE Program Offering of this College.']);
        }
        $feeHeadExists = DB::table('fee_heads')
            ->where('id', $data['fee_head_id'])
            ->where('university_id', $college->university_id)
            ->where('status', 'ACTIVE')
            ->where(function ($q) use ($college) { $q->whereNull('college_id')->orWhere('college_id', $college->id); })
            ->exists();
        if (! $feeHeadExists) {
            throw ValidationException::withMessages(['fee_head_id' => 'Selected Fee Head is not active for this College.']);
        }
        if ($rule && $rule->status === 'ACTIVE') {
            throw ValidationException::withMessages(['rule' => 'Deactivate the Late Fine Rule before editing it.']);
        }
        if ($rule && $rule->charges()->exists()) {
            throw ValidationException::withMessages(['rule' => 'This Late Fine Rule already has calculation history and is immutable. Create a new Rule version instead of changing historical policy.']);
        }
        $payload = [
            'university_id' => $college->university_id,
            'college_id' => $college->id,
            'academic_session_id' => $offering->academic_session_id,
            'college_program_offering_id' => $offering->id,
            'fee_head_id' => $data['fee_head_id'],
            'name' => trim($data['name']),
            'code' => strtoupper(trim($data['code'])),
            'source_type' => 'INSTALLMENT',
            'calculation_type' => $data['calculation_type'],
            'frequency' => $data['frequency'],
            'value' => $data['value'],
            'grace_days' => $data['grace_days'],
            'maximum_fine_amount' => $data['maximum_fine_amount'] ?? null,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $actorId,
        ];
        if ($rule) {
            $this->assertOwner($rule, $college);
            $rule->update($payload);
            return $rule->fresh();
        }
        return FeeLateFineRule::create($payload + ['status' => 'INACTIVE', 'created_by' => $actorId]);
    }

    public function deleteUnusedRule(FeeLateFineRule $rule, College $college, int $actorId): void
    {
        $this->assertOwner($rule, $college);

        if ($rule->status !== 'INACTIVE') {
            throw ValidationException::withMessages([
                'rule' => 'Deactivate the Late Fine Rule before deleting it.',
            ]);
        }

        if ($rule->charges()->exists()) {
            throw ValidationException::withMessages([
                'rule' => 'This Late Fine Rule has calculation history and cannot be deleted. Clean the related test Late Fine Charges first, then try again.',
            ]);
        }

        $before = $rule->toArray();
        $ruleId = (int) $rule->id;
        $rule->delete();

        $this->audit(
            $actorId,
            'FEE_LATE_FINE_RULE_DELETED',
            'fee_late_fine_rule',
            $ruleId,
            $before,
            ['reason' => 'Unused INACTIVE Late Fine Rule removed manually.']
        );
    }

    public function setStatus(FeeLateFineRule $rule, College $college, string $status, int $actorId): void
    {
        $this->assertOwner($rule, $college);
        if ($status === 'ACTIVE') {
            $conflict = FeeLateFineRule::query()
                ->where('college_id', $college->id)
                ->where('college_program_offering_id', $rule->college_program_offering_id)
                ->where('fee_head_id', $rule->fee_head_id)
                ->where('source_type', 'INSTALLMENT')
                ->where('status', 'ACTIVE')
                ->where('id', '!=', $rule->id)
                ->exists();
            if ($conflict) {
                throw ValidationException::withMessages(['status' => 'Another ACTIVE Late Fine Rule already exists for this Program Offering and Fee Head.']);
            }
        }
        $rule->update(['status' => $status, 'updated_by' => $actorId]);
    }

    public function recalculateCollege(College $college, Carbon $asOf, ?int $actorId): array
    {
        $rules = FeeLateFineRule::query()
            ->where('college_id', $college->id)
            ->where('status', 'ACTIVE')
            ->orderBy('id')
            ->get();
        $created = 0; $superseded = 0; $reversed = 0; $unchanged = 0;
        foreach ($rules as $rule) {
            $result = $this->recalculateRule($rule, $college, $asOf, $actorId);
            $created += $result['created'];
            $superseded += $result['superseded'];
            $reversed += $result['reversed'];
            $unchanged += $result['unchanged'];
        }
        return compact('created','superseded','reversed','unchanged');
    }

    public function recalculateRule(FeeLateFineRule $rule, College $college, Carbon $asOf, ?int $actorId): array
    {
        $this->assertOwner($rule, $college);
        $rows = DB::table('fee_installment_schedules as s')
            ->join('fee_demand_items as i', 'i.id', '=', 's.fee_demand_item_id')
            ->join('fee_demands as d', 'd.id', '=', 's.fee_demand_id')
            ->where('d.college_id', $college->id)
            ->where('d.college_program_offering_id', $rule->college_program_offering_id)
            ->where('i.fee_head_id', $rule->fee_head_id)
            ->where('d.status', '!=', 'CANCELLED')
            ->where('s.status', 'ACTIVE')
            ->whereDate('s.due_date', '<', $asOf->toDateString())
            ->select('s.id','s.fee_demand_id','s.fee_demand_item_id','s.amount','s.paid_amount','s.due_date')
            ->orderBy('s.id')
            ->get();

        $stats = ['created'=>0,'superseded'=>0,'reversed'=>0,'unchanged'=>0];
        foreach ($rows as $row) {
            DB::transaction(function () use ($rule,$college,$asOf,$actorId,$row,&$stats) {
                $active = FeeLateFineCharge::query()
                    ->where('fee_late_fine_rule_id', $rule->id)
                    ->where('fee_installment_schedule_id', $row->id)
                    ->where('status', 'ACTIVE')
                    ->lockForUpdate()
                    ->latest('id')
                    ->first();
                $calc = $this->calculate($rule, $row, $asOf);

                // ADR 171: once money has been allocated to a posted fine revision, that
                // financial revision is crystallized and must not be silently reversed or
                // superseded by a later calculator run. Later adjustment/reversal ADRs must
                // use explicit financial history rather than rewriting a paid charge.
                if ($active && $this->postedPaymentAgainstFine((int) $active->id) > 0) {
                    $stats['unchanged']++;
                    return;
                }

                if ($calc['fine_amount'] <= 0) {
                    if ($active) {
                        $active->update(['status'=>'REVERSED','superseded_at'=>now()]);
                        $this->audit($actorId,'FEE_LATE_FINE_REVERSED','fee_late_fine_charge',$active->id,$active->toArray(),['reason'=>'No overdue unpaid base remains.']);
                        $stats['reversed']++;
                    } else $stats['unchanged']++;
                    return;
                }
                if ($active && (float)$active->fine_amount === $calc['fine_amount'] && (float)$active->base_outstanding_amount === $calc['base_outstanding_amount']) {
                    $stats['unchanged']++;
                    return;
                }
                $new = FeeLateFineCharge::create([
                    'fee_late_fine_rule_id'=>$rule->id,'university_id'=>$college->university_id,'college_id'=>$college->id,
                    'fee_demand_id'=>$row->fee_demand_id,'fee_demand_item_id'=>$row->fee_demand_item_id,'fee_installment_schedule_id'=>$row->id,
                    'due_date'=>$row->due_date,'calculated_as_of'=>$asOf->toDateString(),'overdue_days'=>$calc['overdue_days'],
                    'base_outstanding_amount'=>$calc['base_outstanding_amount'],'fine_amount'=>$calc['fine_amount'],'status'=>'ACTIVE','calculated_by'=>$actorId,
                ]);
                if ($active) {
                    $before = $active->toArray();
                    $active->update(['status'=>'SUPERSEDED','superseded_by_id'=>$new->id,'superseded_at'=>now()]);
                    $stats['superseded']++;
                    $this->audit($actorId,'FEE_LATE_FINE_RECALCULATED','fee_late_fine_charge',$new->id,$before,$new->toArray());
                } else {
                    $this->audit($actorId,'FEE_LATE_FINE_POSTED','fee_late_fine_charge',$new->id,null,$new->toArray());
                }
                $stats['created']++;
            });
        }
        return $stats;
    }

    public function activeFineForDemandIds(array $demandIds): array
    {
        if (empty($demandIds) || ! Schema::hasTable('fee_late_fine_charges')) return [];

        $charges = DB::table('fee_late_fine_charges')
            ->whereIn('fee_demand_id', $demandIds)
            ->where('status', 'ACTIVE')
            ->get(['id','fee_demand_id','fine_amount']);

        $paidByCharge = collect();
        if (Schema::hasTable('fee_payment_allocations') && Schema::hasTable('fee_payments') && $charges->isNotEmpty()) {
            $paidByCharge = DB::table('fee_payment_allocations as a')
                ->join('fee_payments as p','p.id','=','a.fee_payment_id')
                ->whereIn('a.fee_late_fine_charge_id',$charges->pluck('id'))
                ->where('a.source_type','LATE_FINE')->where('p.status','POSTED')
                ->select('a.fee_late_fine_charge_id',DB::raw('SUM(a.amount) as paid_amount'))
                ->groupBy('a.fee_late_fine_charge_id')->pluck('paid_amount','a.fee_late_fine_charge_id');
        }

        return $charges->groupBy('fee_demand_id')->map(function ($rows) use ($paidByCharge) {
            return (float) $rows->sum(fn($r)=>max(0,round((float)$r->fine_amount-(float)($paidByCharge[$r->id]??0),2)));
        })->all();
    }

    private function postedPaymentAgainstFine(int $chargeId): float
    {
        if (! Schema::hasTable('fee_payment_allocations') || ! Schema::hasTable('fee_payments')) return 0.0;
        return (float) DB::table('fee_payment_allocations as a')
            ->join('fee_payments as p','p.id','=','a.fee_payment_id')
            ->where('a.fee_late_fine_charge_id',$chargeId)->where('a.source_type','LATE_FINE')->where('p.status','POSTED')
            ->sum('a.amount');
    }

    public function assertOwner(FeeLateFineRule $rule, College $college): void
    {
        abort_unless((int)$rule->college_id === (int)$college->id && (int)$rule->university_id === (int)$college->university_id, 404);
    }

    private function calculate(FeeLateFineRule $rule, object $row, Carbon $asOf): array
    {
        $base = max(0, round((float)$row->amount - (float)($row->paid_amount ?? 0), 2));
        $due = Carbon::parse($row->due_date)->startOfDay();
        $fineStartsAfter = $due->copy()->addDays((int)$rule->grace_days);
        $days = max(0, $fineStartsAfter->diffInDays($asOf->copy()->startOfDay(), false));
        if ($days <= 0 || $base <= 0) return ['base_outstanding_amount'=>$base,'overdue_days'=>0,'fine_amount'=>0.0];
        $units = match ($rule->frequency) {
            'ONE_TIME' => 1,
            'PER_DAY' => $days,
            'PER_WEEK' => (int) ceil($days / 7),
            default => 1,
        };
        $fine = $rule->calculation_type === 'PERCENTAGE'
            ? $base * ((float)$rule->value / 100) * $units
            : (float)$rule->value * $units;
        if ($rule->maximum_fine_amount !== null) $fine = min($fine, (float)$rule->maximum_fine_amount);
        return ['base_outstanding_amount'=>$base,'overdue_days'=>$days,'fine_amount'=>round(max(0,$fine),2)];
    }

    private function audit(?int $actorId, string $event, string $resourceType, int $resourceId, mixed $before, mixed $after): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id'=>$actorId,'event'=>$event,'resource_type'=>$resourceType,'resource_id'=>$resourceId,
            'before'=>$before === null ? null : json_encode($before),'after'=>$after === null ? null : json_encode($after),
            'ip_address'=>app()->runningInConsole()?null:request()->ip(),'created_at'=>now(),
        ]);
    }
}
