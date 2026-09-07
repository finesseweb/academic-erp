<?php

namespace App\Services;

use App\Models\College;
use App\Models\FeeDemand;
use App\Models\FeeScholarshipScheme;
use App\Models\FeeStudentBenefit;
use App\Models\FeeStudentBenefitItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeeStudentBenefitService
{
    public function applicableSchemes(College $college, FeeDemand $demand): array
    {
        $context = $this->demandContext($college, $demand);
        $schemes = FeeScholarshipScheme::query()
            ->with(['feeHeads:id,name,code', 'reservationCategories:id,name,code'])
            ->where('university_id', $context['university_id'])
            ->where('academic_session_id', $demand->academic_session_id)
            ->where('status', 'ACTIVE')
            ->where(function ($query) use ($college, $context) {
                $query->where(function ($universityQuery) use ($context) {
                    $universityQuery->whereNull('college_id')
                        ->where(function ($programQuery) use ($context) {
                            $programQuery->whereNull('program_template_id')
                                ->orWhere('program_template_id', $context['program_template_id']);
                        });
                })->orWhere(function ($collegeQuery) use ($college, $context) {
                    $collegeQuery->where('college_id', $college->id)
                        ->where('college_program_offering_id', $context['offering_id']);
                });
            })
            ->orderByRaw('college_id is null desc')
            ->orderBy('name')
            ->get();

        return $schemes->map(function (FeeScholarshipScheme $scheme) use ($demand, $context) {
            $preview = $this->preview($scheme, $demand, $context);
            return [
                'id' => $scheme->id,
                'name' => $scheme->name,
                'code' => $scheme->code,
                'owner_type' => $scheme->college_id ? 'COLLEGE' : 'UNIVERSITY',
                'benefit_type' => $scheme->benefit_type,
                'calculation_type' => $scheme->calculation_type,
                'benefit_value' => $scheme->benefit_value,
                'maximum_benefit_amount' => $scheme->maximum_benefit_amount,
                'approval_mode' => $scheme->approval_mode,
                'eligibility_mode' => $scheme->eligibility_mode,
                'fee_heads' => $scheme->feeHeads->map->only(['id','name','code'])->values(),
                'eligible' => $preview['eligible'],
                'eligibility_reason' => $preview['reason'],
                'eligible_base_amount' => $preview['eligible_base_amount'],
                'calculated_benefit_amount' => $preview['calculated_benefit_amount'],
            ];
        })->values()->all();
    }

    public function assign(College $college, FeeDemand $demand, FeeScholarshipScheme $scheme, int $actorId, ?string $note = null): FeeStudentBenefit
    {
        return DB::transaction(function () use ($college, $demand, $scheme, $actorId, $note) {
            $lockedDemand = FeeDemand::query()->whereKey($demand->id)->lockForUpdate()->firstOrFail();
            $context = $this->demandContext($college, $lockedDemand);
            $this->assertSchemeInScope($scheme, $college, $lockedDemand, $context);

            $duplicate = FeeStudentBenefit::query()
                ->where('fee_demand_id', $lockedDemand->id)
                ->where('fee_scholarship_scheme_id', $scheme->id)
                ->whereIn('status', ['PENDING','APPROVED'])
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['scheme_id' => 'This scheme is already pending or approved for the selected Fee Demand.']);
            }

            $preview = $this->preview($scheme->loadMissing(['feeHeads:id,name,code','reservationCategories:id,name,code']), $lockedDemand, $context);
            if (! $preview['eligible']) {
                throw ValidationException::withMessages(['scheme_id' => $preview['reason'] ?: 'The selected scheme is not eligible for this student demand.']);
            }
            if ($preview['calculated_benefit_amount'] <= 0) {
                throw ValidationException::withMessages(['scheme_id' => 'No unadjusted eligible fee amount remains for this scheme.']);
            }

            $automatic = $scheme->approval_mode === 'AUTOMATIC';
            $benefit = FeeStudentBenefit::create([
                'university_id' => $context['university_id'],
                'college_id' => $college->id,
                'admission_id' => $lockedDemand->admission_id,
                'fee_demand_id' => $lockedDemand->id,
                'fee_scholarship_scheme_id' => $scheme->id,
                'application_mode' => $automatic ? 'AUTOMATIC' : 'MANUAL_ASSIGNMENT',
                'status' => 'PENDING',
                'eligible_base_amount' => $preview['eligible_base_amount'],
                'calculated_benefit_amount' => $preview['calculated_benefit_amount'],
                'sanctioned_amount' => null,
                'eligibility_snapshot' => $preview['snapshot'],
                'scheme_name_snapshot' => $scheme->name,
                'scheme_code_snapshot' => $scheme->code,
                'benefit_type_snapshot' => $scheme->benefit_type,
                'calculation_type_snapshot' => $scheme->calculation_type,
                'benefit_value_snapshot' => $scheme->benefit_value,
                'maximum_benefit_amount_snapshot' => $scheme->maximum_benefit_amount,
                'application_note' => filled($note) ? trim($note) : null,
                'applied_at' => now(),
                'applied_by' => $actorId,
            ]);

            foreach ($preview['items'] as $item) {
                FeeStudentBenefitItem::create([
                    'fee_student_benefit_id' => $benefit->id,
                    'fee_demand_item_id' => $item['fee_demand_item_id'],
                    'fee_head_id' => $item['fee_head_id'],
                    'eligible_amount' => $item['eligible_amount'],
                    'calculated_amount' => $item['calculated_amount'],
                    'sanctioned_amount' => null,
                ]);
            }

            if ($automatic) {
                $this->approveLocked($benefit, $lockedDemand, (float) $preview['calculated_benefit_amount'], $actorId, 'Automatically sanctioned by scheme policy.');
            }

            return $benefit->fresh('items');
        });
    }

    public function approve(College $college, FeeStudentBenefit $benefit, float $sanctionedAmount, int $actorId, ?string $note = null): FeeStudentBenefit
    {
        return DB::transaction(function () use ($college, $benefit, $sanctionedAmount, $actorId, $note) {
            $locked = FeeStudentBenefit::query()->whereKey($benefit->id)->lockForUpdate()->firstOrFail();
            $this->assertCollege($locked, $college);
            if ($locked->status !== 'PENDING') {
                throw ValidationException::withMessages(['benefit' => 'Only a PENDING benefit can be approved.']);
            }
            if ($sanctionedAmount <= 0 || $sanctionedAmount > (float) $locked->calculated_benefit_amount) {
                throw ValidationException::withMessages(['sanctioned_amount' => 'Sanction amount must be greater than zero and cannot exceed the calculated eligible benefit.']);
            }
            $demand = FeeDemand::query()->whereKey($locked->fee_demand_id)->lockForUpdate()->firstOrFail();
            if ($demand->status === 'CANCELLED') {
                throw ValidationException::withMessages(['benefit' => 'Cannot approve a benefit against a cancelled Fee Demand.']);
            }
            $this->approveLocked($locked, $demand, $sanctionedAmount, $actorId, $note);
            return $locked->fresh('items');
        });
    }

    public function reject(College $college, FeeStudentBenefit $benefit, int $actorId, string $note): void
    {
        $this->assertCollege($benefit, $college);
        if ($benefit->status !== 'PENDING') {
            throw ValidationException::withMessages(['benefit' => 'Only a PENDING benefit can be rejected.']);
        }
        $benefit->update([
            'status' => 'REJECTED',
            'decision_note' => trim($note),
            'decided_at' => now(),
            'decided_by' => $actorId,
        ]);
    }

    public function cancel(College $college, FeeStudentBenefit $benefit, int $actorId, string $reason): void
    {
        $this->assertCollege($benefit, $college);
        if ($benefit->status === 'APPROVED') {
            throw ValidationException::withMessages(['benefit' => 'Approved benefits cannot be cancelled directly. A financial reversal workflow is required.']);
        }
        if ($benefit->status === 'CANCELLED') {
            throw ValidationException::withMessages(['benefit' => 'This benefit is already cancelled.']);
        }
        $benefit->update([
            'status' => 'CANCELLED',
            'cancelled_at' => now(),
            'cancelled_by' => $actorId,
            'cancellation_reason' => trim($reason),
        ]);
    }

    private function approveLocked(FeeStudentBenefit $benefit, FeeDemand $demand, float $sanctionedAmount, int $actorId, ?string $note): void
    {
        $items = FeeStudentBenefitItem::query()->where('fee_student_benefit_id', $benefit->id)->orderBy('id')->lockForUpdate()->get();
        $calculatedTotal = max((float) $items->sum(fn ($i) => (float) $i->calculated_amount), 0.01);
        $remaining = round($sanctionedAmount, 2);
        $count = $items->count();
        foreach ($items as $index => $item) {
            if ($index === $count - 1) {
                $share = $remaining;
            } else {
                $share = round($sanctionedAmount * ((float) $item->calculated_amount / $calculatedTotal), 2);
                $share = min($share, $remaining);
            }
            $share = min($share, (float) $item->eligible_amount);
            $item->update(['sanctioned_amount' => $share]);
            $remaining = round($remaining - $share, 2);
        }
        if ($remaining > 0.01) {
            throw ValidationException::withMessages(['sanctioned_amount' => 'Sanction amount exceeds the remaining eligible fee-item capacity.']);
        }

        $benefit->update([
            'status' => 'APPROVED',
            'sanctioned_amount' => round($sanctionedAmount, 2),
            'decision_note' => filled($note) ? trim($note) : null,
            'decided_at' => now(),
            'decided_by' => $actorId,
        ]);

        $newAdjusted = round((float) $demand->adjusted_amount + $sanctionedAmount, 2);
        $outstanding = max(round((float) $demand->total_amount - (float) $demand->paid_amount - $newAdjusted, 2), 0);
        $status = $outstanding <= 0 ? 'CLEARED' : (($newAdjusted + (float) $demand->paid_amount) > 0 ? 'PARTIALLY_CLEARED' : 'OPEN');
        $demand->update([
            'adjusted_amount' => $newAdjusted,
            'outstanding_amount' => $outstanding,
            'status' => $status,
        ]);
    }

    private function preview(FeeScholarshipScheme $scheme, FeeDemand $demand, array $context): array
    {
        $categoryIds = $context['reservation_category_ids'];
        if ($scheme->eligibility_mode === 'RESERVATION_CATEGORY') {
            $allowed = $scheme->reservationCategories->pluck('id')->map(fn ($id) => (int) $id)->all();
            if (! array_intersect($allowed, $categoryIds)) {
                return $this->ineligible('Student admission category does not match this scheme.', $context);
            }
        }

        $headIds = $scheme->feeHeads->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (! $headIds) {
            return $this->ineligible('Scheme has no applicable Fee Head.', $context);
        }

        $demandItems = DB::table('fee_demand_items')
            ->where('fee_demand_id', $demand->id)
            ->whereIn('fee_head_id', $headIds)
            ->orderBy('display_order')->orderBy('id')
            ->get(['id','fee_head_id','fee_head_name','amount']);
        if ($demandItems->isEmpty()) {
            return $this->ineligible('Selected Fee Demand has no fee item covered by this scheme.', $context);
        }

        $approvedByItem = DB::table('fee_student_benefit_items as bi')
            ->join('fee_student_benefits as b', 'b.id', '=', 'bi.fee_student_benefit_id')
            ->where('b.fee_demand_id', $demand->id)
            ->where('b.status', 'APPROVED')
            ->whereIn('bi.fee_demand_item_id', $demandItems->pluck('id'))
            ->groupBy('bi.fee_demand_item_id')
            ->selectRaw('bi.fee_demand_item_id, COALESCE(SUM(bi.sanctioned_amount),0) as approved_amount')
            ->pluck('approved_amount', 'fee_demand_item_id');

        $eligibleRows = collect();
        foreach ($demandItems as $item) {
            $remaining = max(round((float) $item->amount - (float) ($approvedByItem[$item->id] ?? 0), 2), 0);
            if ($remaining > 0) {
                $eligibleRows->push([
                    'fee_demand_item_id' => (int) $item->id,
                    'fee_head_id' => (int) $item->fee_head_id,
                    'fee_head_name' => $item->fee_head_name,
                    'eligible_amount' => $remaining,
                ]);
            }
        }
        $base = round((float) $eligibleRows->sum('eligible_amount'), 2);
        if ($base <= 0) {
            return $this->ineligible('All covered fee items are already fully adjusted by approved benefits.', $context);
        }

        $calculated = $scheme->calculation_type === 'FIXED'
            ? min((float) $scheme->benefit_value, $base)
            : $base * ((float) $scheme->benefit_value / 100);
        if ($scheme->calculation_type === 'PERCENTAGE' && $scheme->maximum_benefit_amount !== null) {
            $calculated = min($calculated, (float) $scheme->maximum_benefit_amount);
        }
        $calculated = round(min($calculated, $base), 2);

        $items = $this->allocateCalculated($eligibleRows, $base, $calculated);
        return [
            'eligible' => true,
            'reason' => null,
            'eligible_base_amount' => $base,
            'calculated_benefit_amount' => $calculated,
            'items' => $items,
            'snapshot' => [
                'reservation_category_ids' => $context['reservation_category_ids'],
                'reservation_categories' => $context['reservation_categories'],
                'eligibility_mode' => $scheme->eligibility_mode,
                'eligible_fee_heads' => $scheme->feeHeads->map(fn ($h) => ['id'=>$h->id,'code'=>$h->code,'name'=>$h->name])->values()->all(),
                'stacking_rule' => 'APPROVAL_ORDER_REMAINING_ELIGIBLE_BASE',
            ],
        ];
    }

    private function allocateCalculated(Collection $rows, float $base, float $total): array
    {
        $remaining = $total;
        $result = [];
        $count = $rows->count();
        foreach ($rows->values() as $index => $row) {
            $amount = $index === $count - 1
                ? $remaining
                : round($total * ((float) $row['eligible_amount'] / max($base, 0.01)), 2);
            $amount = min($amount, (float) $row['eligible_amount'], $remaining);
            $result[] = $row + ['calculated_amount' => round($amount, 2)];
            $remaining = round($remaining - $amount, 2);
        }
        return $result;
    }

    private function demandContext(College $college, FeeDemand $demand): array
    {
        if ((int) $demand->college_id !== (int) $college->id) abort(404);
        if ($demand->status === 'CANCELLED') {
            throw ValidationException::withMessages(['demand_id' => 'Cancelled Fee Demand cannot receive a scholarship / concession / waiver.']);
        }
        $offering = DB::table('college_program_offerings')
            ->where('id', $demand->college_program_offering_id)
            ->where('college_id', $college->id)
            ->first(['id','program_template_id','academic_session_id']);
        if (! $offering) throw ValidationException::withMessages(['demand_id' => 'Fee Demand Program Offering context is no longer available.']);

        $admission = DB::table('admissions')->where('id', $demand->admission_id)->where('college_id', $college->id)->first();
        if (! $admission) throw ValidationException::withMessages(['demand_id' => 'Admission context is not available for this Fee Demand.']);

        $allocationId = $admission->college_admission_seat_allocation_id ?? null;
        $categoryIds = [];
        $categoryLabels = [];
        if ($allocationId) {
            $allocation = DB::table('college_admission_seat_allocations')->where('id', $allocationId)->first(['physical_reservation_category_id','physical_category_code','physical_category_name']);
            if ($allocation?->physical_reservation_category_id) {
                $categoryIds[] = (int) $allocation->physical_reservation_category_id;
                $categoryLabels[] = trim(($allocation->physical_category_name ?: 'Category').' ('.($allocation->physical_category_code ?: '-').')');
            }
            $horizontal = DB::table('college_admission_seat_allocation_horizontal_categories')
                ->where('college_admission_seat_allocation_id', $allocationId)
                ->get(['reservation_category_id','category_name','category_code']);
            foreach ($horizontal as $row) {
                $categoryIds[] = (int) $row->reservation_category_id;
                $categoryLabels[] = trim($row->category_name.' ('.$row->category_code.')');
            }
        }

        return [
            'university_id' => (int) $college->university_id,
            'offering_id' => (int) $offering->id,
            'program_template_id' => (int) $offering->program_template_id,
            'reservation_category_ids' => array_values(array_unique($categoryIds)),
            'reservation_categories' => array_values(array_unique($categoryLabels)),
        ];
    }

    private function assertSchemeInScope(FeeScholarshipScheme $scheme, College $college, FeeDemand $demand, array $context): void
    {
        if ($scheme->status !== 'ACTIVE' || (int) $scheme->university_id !== (int) $context['university_id'] || (int) $scheme->academic_session_id !== (int) $demand->academic_session_id) {
            throw ValidationException::withMessages(['scheme_id' => 'Selected scheme is not ACTIVE in this Fee Demand context.']);
        }
        if ($scheme->college_id !== null) {
            if ((int) $scheme->college_id !== (int) $college->id || (int) $scheme->college_program_offering_id !== (int) $context['offering_id']) {
                throw ValidationException::withMessages(['scheme_id' => 'Selected College scheme does not belong to this Program Offering.']);
            }
        } elseif ($scheme->program_template_id !== null && (int) $scheme->program_template_id !== (int) $context['program_template_id']) {
            throw ValidationException::withMessages(['scheme_id' => 'Selected University scheme does not apply to this Program.']);
        }
    }

    private function assertCollege(FeeStudentBenefit $benefit, College $college): void
    {
        abort_unless((int) $benefit->college_id === (int) $college->id, 404);
    }

    private function ineligible(string $reason, array $context): array
    {
        return [
            'eligible' => false,
            'reason' => $reason,
            'eligible_base_amount' => 0,
            'calculated_benefit_amount' => 0,
            'items' => [],
            'snapshot' => [
                'reservation_category_ids' => $context['reservation_category_ids'],
                'reservation_categories' => $context['reservation_categories'],
            ],
        ];
    }
}
