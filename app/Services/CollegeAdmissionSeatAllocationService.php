<?php

namespace App\Services;

use App\Models\College;
use App\Models\CollegeAdmissionDocumentVerification;
use App\Models\CollegeAdmissionMeritEntry;
use App\Models\CollegeAdmissionSeatAllocation;
use App\Models\CollegeAdmissionSeatAllocationHorizontalCategory;
use App\Models\CollegeAdmissionSelectionRule;
use App\Models\CollegeProgramReservationAllocation;
use App\Models\CollegeProgramReservationPlan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CollegeAdmissionSeatAllocationService
{
    public function screen(College $college, CollegeAdmissionSelectionRule $rule): array
    {
        $this->assertRuleOwnedByCollege($college, $rule);

        $rule->loadMissing([
            'intake.offering.programTemplate.degree.degreeLevel:id,name,code',
            'intake.offering.academicSession:id,name,code,is_current',
            'reservationPlan.allocations.category:id,name,code,nature,status',
        ]);

        $meritRows = CollegeAdmissionMeritEntry::query()
            ->where('college_id', $college->id)
            ->where('college_admission_selection_rule_id', $rule->id)
            ->with([
                'application:id,application_no,candidate_name,email,status',
                'application.documentVerification:id,college_admission_application_id,status,finalized_at',
                'choice:id,college_admission_application_id,preference_no,college_program_intake_id,college_program_reservation_plan_id,college_admission_selection_rule_id,bucket_type,bucket_key,basis_capacity,eligibility_status',
                'application.academicPreference.discipline:id,name,code',
                'application.academicPreference.specialization:id,name,code',
            ])
            ->orderBy('rank')
            ->get();

        $allocationByMerit = CollegeAdmissionSeatAllocation::query()
            ->where('college_admission_selection_rule_id', $rule->id)
            ->with('horizontalCategories:id,college_admission_seat_allocation_id,reservation_category_id,category_code,category_name,fulfills_target')
            ->get()
            ->keyBy('college_admission_merit_entry_id');

        $capacity = $this->capacitySnapshot($college, $rule);

        return [
            'summary' => [
                'roster_count' => $meritRows->count(),
                'allocated_count' => $allocationByMerit->where('status', 'ALLOCATED')->count(),
                'cancelled_count' => $allocationByMerit->where('status', 'CANCELLED')->count(),
                'remaining_physical_seats' => max(0, $capacity['basis_capacity'] - $capacity['total_used']),
            ],
            'capacity' => $capacity,
            'rows' => $meritRows->map(function (CollegeAdmissionMeritEntry $merit) use ($allocationByMerit) {
                /** @var CollegeAdmissionSeatAllocation|null $allocation */
                $allocation = $allocationByMerit->get($merit->id);
                $preference = $merit->application?->academicPreference;

                return [
                    'merit_entry_id' => $merit->id,
                    'rank' => (int) $merit->rank,
                    'final_weighted_score' => (float) $merit->final_weighted_score,
                    'application_id' => $merit->college_admission_application_id,
                    'choice_id' => $merit->college_admission_application_choice_id,
                    'preference_no' => (int) ($merit->choice?->preference_no ?? 1),
                    'application_no' => $merit->application?->application_no,
                    'candidate_name' => $merit->application?->candidate_name,
                    'document_verification' => $merit->application?->documentVerification ? [
                        'id' => $merit->application->documentVerification->id,
                        'status' => $merit->application->documentVerification->status,
                        'finalized_at' => optional($merit->application->documentVerification->finalized_at)->toIso8601String(),
                    ] : null,
                    'discipline_name' => $preference?->discipline?->name,
                    'discipline_code' => $preference?->discipline?->code,
                    'specialization_name' => $preference?->specialization?->name,
                    'specialization_code' => $preference?->specialization?->code,
                    'allocation' => $allocation ? [
                        'id' => $allocation->id,
                        'status' => $allocation->status,
                        'physical_seat_type' => $allocation->physical_seat_type,
                        'physical_category_code' => $allocation->physical_category_code,
                        'physical_category_name' => $allocation->physical_category_name,
                        'allocation_round' => (int) $allocation->allocation_round,
                        'decision_note' => $allocation->decision_note,
                        'allocated_at' => optional($allocation->allocated_at)->toIso8601String(),
                        'cancellation_reason' => $allocation->cancellation_reason,
                        'horizontal_categories' => $allocation->horizontalCategories->map(fn ($row) => [
                            'id' => $row->reservation_category_id,
                            'code' => $row->category_code,
                            'name' => $row->category_name,
                            'fulfills_target' => (bool) $row->fulfills_target,
                        ])->values()->all(),
                    ] : null,
                ];
            })->values()->all(),
        ];
    }

    public function allocate(
        College $college,
        CollegeAdmissionMeritEntry $merit,
        array $data,
        int $actorId,
        ?string $ip
    ): CollegeAdmissionSeatAllocation {
        $this->assertMeritOwnedByCollege($college, $merit);

        return DB::transaction(function () use ($college, $merit, $data, $actorId, $ip) {
            $lockedMerit = CollegeAdmissionMeritEntry::query()
                ->whereKey($merit->id)
                ->lockForUpdate()
                ->firstOrFail();
            $lockedMerit->loadMissing(['choice.application', 'selectionRule']);

            $choice = $lockedMerit->choice;
            $application = $choice?->application;
            $rule = $lockedMerit->selectionRule;

            if (! $choice || ! $application || ! $rule) {
                throw ValidationException::withMessages([
                    'allocation' => 'Merit / Roster row is missing its locked Application Choice or Selection Rule context.',
                ]);
            }

            $this->assertRuleOwnedByCollege($college, $rule);
            if ($application->status !== 'SUBMITTED' || $choice->eligibility_status !== 'ELIGIBLE') {
                throw ValidationException::withMessages([
                    'allocation' => 'Only a SUBMITTED + ELIGIBLE ranked Application Choice can receive a seat.',
                ]);
            }

            if ((int) $choice->college_program_intake_id !== (int) $lockedMerit->college_program_intake_id
                || $choice->bucket_key !== $lockedMerit->bucket_key
                || (int) $choice->college_admission_selection_rule_id !== (int) $lockedMerit->college_admission_selection_rule_id) {
                throw ValidationException::withMessages([
                    'allocation' => 'The ranked Application Choice no longer matches its immutable Merit / Roster scope.',
                ]);
            }

            DB::table('college_admission_applications')
                ->where('id', $application->id)
                ->lockForUpdate()
                ->get();

            $documentVerification = CollegeAdmissionDocumentVerification::query()
                ->where('college_admission_application_id', $application->id)
                ->lockForUpdate()
                ->first();

            if (! $documentVerification || $documentVerification->status !== 'VERIFIED') {
                throw ValidationException::withMessages([
                    'allocation' => 'Document Verification must be finalized as VERIFIED before this ranked candidate can consume a seat.',
                ]);
            }

            $existing = CollegeAdmissionSeatAllocation::query()
                ->where('college_admission_merit_entry_id', $lockedMerit->id)
                ->lockForUpdate()
                ->first();

            CollegeAdmissionSeatAllocation::query()
                ->where('college_admission_application_id', $application->id)
                ->lockForUpdate()
                ->get();

            $otherActive = CollegeAdmissionSeatAllocation::query()
                ->where('college_admission_application_id', $application->id)
                ->where('status', 'ALLOCATED')
                ->when($existing, fn ($query) => $query->where('id', '<>', $existing->id))
                ->exists();

            if ($otherActive) {
                throw ValidationException::withMessages([
                    'allocation' => 'This application already consumes a physical seat through another ranked Program Choice. Cancel that allocation before assigning another seat.',
                ]);
            }

            if ($existing && $existing->status === 'ALLOCATED') {
                throw ValidationException::withMessages([
                    'allocation' => 'This Merit / Roster row already has an active seat allocation.',
                ]);
            }

            $rule->loadMissing('reservationPlan.allocations.category');
            $seatContext = $this->lockedSeatContext($college, $rule);
            $physicalCategoryId = isset($data['physical_reservation_category_id']) && $data['physical_reservation_category_id'] !== ''
                ? (int) $data['physical_reservation_category_id']
                : null;

            $physical = $this->validatePhysicalSeat($seatContext, $physicalCategoryId, $existing?->id);
            $horizontalIds = collect($data['horizontal_category_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            $targetIds = collect($data['horizontal_target_category_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            $horizontalRows = $this->validateHorizontalSelections($seatContext, $horizontalIds, $targetIds, $existing?->id);

            $now = now();
            $values = [
                'college_id' => $college->id,
                'college_program_intake_id' => $lockedMerit->college_program_intake_id,
                'bucket_type' => $rule->bucket_type,
                'bucket_key' => $lockedMerit->bucket_key,
                'college_program_reservation_plan_id' => $rule->college_program_reservation_plan_id,
                'college_admission_merit_entry_id' => $lockedMerit->id,
                'college_admission_application_id' => $lockedMerit->college_admission_application_id,
                'college_admission_application_choice_id' => $lockedMerit->college_admission_application_choice_id,
                'college_admission_document_verification_id' => $documentVerification->id,
                'college_admission_score_id' => $lockedMerit->college_admission_score_id,
                'college_admission_selection_rule_id' => $lockedMerit->college_admission_selection_rule_id,
                'merit_rank' => $lockedMerit->rank,
                'final_weighted_score' => $lockedMerit->final_weighted_score,
                'physical_seat_type' => $physical['seat_type'],
                'physical_reservation_category_id' => $physical['category_id'],
                'physical_category_code' => $physical['category_code'],
                'physical_category_name' => $physical['category_name'],
                'allocation_round' => max(1, (int) ($data['allocation_round'] ?? 1)),
                'status' => 'ALLOCATED',
                'decision_note' => filled($data['decision_note'] ?? null) ? trim((string) $data['decision_note']) : null,
                'allocated_at' => $now,
                'allocated_by' => $actorId,
                'cancelled_at' => null,
                'cancelled_by' => null,
                'cancellation_reason' => null,
            ];

            $before = $existing?->load('horizontalCategories')->toArray();

            if ($existing) {
                $existing->update($values);
                $allocation = $existing->fresh();
                $allocation->horizontalCategories()->delete();
                $event = 'COLLEGE_ADMISSION_SEAT_REALLOCATED';
            } else {
                $allocation = CollegeAdmissionSeatAllocation::create($values);
                $event = 'COLLEGE_ADMISSION_SEAT_ALLOCATED';
            }

            foreach ($horizontalRows as $row) {
                CollegeAdmissionSeatAllocationHorizontalCategory::create([
                    'college_admission_seat_allocation_id' => $allocation->id,
                    'reservation_category_id' => $row['category_id'],
                    'category_code' => $row['category_code'],
                    'category_name' => $row['category_name'],
                    'fulfills_target' => $row['fulfills_target'],
                    'created_by' => $actorId,
                ]);
            }

            $allocation->load('horizontalCategories');
            $this->audit($event, $allocation, $college, $actorId, $ip, $before, $allocation->toArray());

            return $allocation;
        });
    }

    public function cancel(
        College $college,
        CollegeAdmissionSeatAllocation $allocation,
        string $reason,
        int $actorId,
        ?string $ip
    ): void {
        $this->assertAllocationOwnedByCollege($college, $allocation);

        DB::transaction(function () use ($college, $allocation, $reason, $actorId, $ip) {
            $locked = CollegeAdmissionSeatAllocation::query()->whereKey($allocation->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'CANCELLED') {
                return;
            }

            if (Schema::hasTable('admissions')
                && Schema::hasColumn('admissions', 'college_admission_seat_allocation_id')
                && DB::table('admissions')->where('college_admission_seat_allocation_id', $locked->id)->exists()) {
                throw ValidationException::withMessages([
                    'allocation' => 'This seat allocation is already consumed by Admission Confirmation and cannot be cancelled here.',
                ]);
            }

            $before = $locked->load('horizontalCategories')->toArray();
            $locked->update([
                'status' => 'CANCELLED',
                'cancelled_at' => now(),
                'cancelled_by' => $actorId,
                'cancellation_reason' => trim($reason),
            ]);

            $this->audit(
                'COLLEGE_ADMISSION_SEAT_ALLOCATION_CANCELLED',
                $locked,
                $college,
                $actorId,
                $ip,
                $before,
                $locked->fresh()->load('horizontalCategories')->toArray()
            );
        });
    }

    private function lockedSeatContext(College $college, CollegeAdmissionSelectionRule $rule): array
    {
        // Serialize physical capacity checks at the Intake parent even when the
        // bucket has no Reservation Plan, preventing concurrent over-allocation.
        DB::table('college_program_intakes')
            ->where('id', $rule->college_program_intake_id)
            ->lockForUpdate()
            ->get();

        $plan = null;
        $vertical = collect();
        $horizontal = collect();

        if ($rule->college_program_reservation_plan_id) {
            $plan = CollegeProgramReservationPlan::query()
                ->whereKey($rule->college_program_reservation_plan_id)
                ->lockForUpdate()
                ->with(['intake.offering', 'allocations.category'])
                ->firstOrFail();

            if ((int) $plan->intake?->offering?->college_id !== (int) $college->id
                || (int) $plan->college_program_intake_id !== (int) $rule->college_program_intake_id
                || $plan->bucket_key !== $rule->bucket_key) {
                throw ValidationException::withMessages([
                    'allocation' => 'The locked Reservation Plan no longer matches this Selection Rule seat bucket.',
                ]);
            }

            if ($plan->status !== 'ACTIVE') {
                throw ValidationException::withMessages([
                    'allocation' => 'The exact Reservation Plan locked by this Selection Rule must remain ACTIVE until Seat Allocation is complete.',
                ]);
            }

            CollegeProgramReservationAllocation::query()
                ->where('college_program_reservation_plan_id', $plan->id)
                ->lockForUpdate()
                ->get();

            $active = $plan->allocations->where('status', 'ACTIVE')->filter(fn ($row) => $row->category);
            $vertical = $active->filter(fn ($row) => $row->category->nature === 'VERTICAL')->values();
            $horizontal = $active->filter(fn ($row) => $row->category->nature === 'HORIZONTAL')->values();
        }

        $basis = (int) $rule->basis_capacity;
        $verticalCapacity = (int) $vertical->sum('seat_capacity');

        return [
            'rule' => $rule,
            'plan' => $plan,
            'basis_capacity' => $basis,
            'open_capacity' => max(0, $basis - $verticalCapacity),
            'vertical' => $vertical,
            'horizontal' => $horizontal,
        ];
    }

    private function validatePhysicalSeat(array $context, ?int $categoryId, ?int $ignoreAllocationId): array
    {
        $rule = $context['rule'];
        $baseQuery = CollegeAdmissionSeatAllocation::query()
            ->where('college_program_intake_id', $rule->college_program_intake_id)
            ->where('bucket_key', $rule->bucket_key)
            ->where('status', 'ALLOCATED');

        if ($ignoreAllocationId) {
            $baseQuery->where('id', '<>', $ignoreAllocationId);
        }

        $totalUsed = (clone $baseQuery)->count();
        if ($totalUsed >= (int) $context['basis_capacity']) {
            throw ValidationException::withMessages(['physical_reservation_category_id' => 'This admission seat bucket is already full.']);
        }

        if (! $context['plan']) {
            if ($categoryId !== null) {
                throw ValidationException::withMessages(['physical_reservation_category_id' => 'This seat bucket has no Reservation Plan. Allocate it as Open / Unreserved.']);
            }

            $used = (clone $baseQuery)->whereNull('physical_reservation_category_id')->count();
            if ($used >= (int) $context['open_capacity']) {
                throw ValidationException::withMessages(['physical_reservation_category_id' => 'No Open / Unreserved seat remains in this bucket.']);
            }

            return ['seat_type' => 'OPEN', 'category_id' => null, 'category_code' => null, 'category_name' => 'Open / Unreserved'];
        }

        if ($categoryId === null) {
            $used = (clone $baseQuery)->whereNull('physical_reservation_category_id')->count();
            if ($used >= (int) $context['open_capacity']) {
                throw ValidationException::withMessages(['physical_reservation_category_id' => 'No Open / Unreserved seat remains in this Reservation Plan.']);
            }

            return ['seat_type' => 'OPEN', 'category_id' => null, 'category_code' => null, 'category_name' => 'Open / Unreserved'];
        }

        $quota = $context['vertical']->first(fn ($row) => (int) $row->reservation_category_id === $categoryId);
        if (! $quota) {
            throw ValidationException::withMessages(['physical_reservation_category_id' => 'Select a configured Vertical reservation category for this exact seat bucket.']);
        }

        $used = (clone $baseQuery)->where('physical_reservation_category_id', $categoryId)->count();
        if ($used >= (int) $quota->seat_capacity) {
            throw ValidationException::withMessages(['physical_reservation_category_id' => $quota->category->name.' reserved seat capacity is already fully consumed.']);
        }

        return [
            'seat_type' => 'RESERVED',
            'category_id' => $quota->category->id,
            'category_code' => $quota->category->code,
            'category_name' => $quota->category->name,
        ];
    }

    private function validateHorizontalSelections(
        array $context,
        Collection $selectedIds,
        Collection $targetIds,
        ?int $ignoreAllocationId
    ): array {
        if ($selectedIds->isEmpty() && $targetIds->isEmpty()) {
            return [];
        }

        if (! $context['plan']) {
            throw ValidationException::withMessages(['horizontal_category_ids' => 'Horizontal quota can be recorded only when this seat bucket has a Reservation Plan.']);
        }

        if ($targetIds->diff($selectedIds)->isNotEmpty()) {
            throw ValidationException::withMessages(['horizontal_target_category_ids' => 'A Horizontal category can count toward its target only when it is selected for the candidate.']);
        }

        $configured = $context['horizontal']->keyBy('reservation_category_id');
        $invalid = $selectedIds->filter(fn ($id) => ! $configured->has($id));
        if ($invalid->isNotEmpty()) {
            throw ValidationException::withMessages(['horizontal_category_ids' => 'One selected Horizontal category is not configured for this exact Reservation Plan.']);
        }

        $rows = [];
        foreach ($selectedIds as $categoryId) {
            $quota = $configured->get($categoryId);
            $fulfillsTarget = $targetIds->contains($categoryId);

            if ($fulfillsTarget) {
                $fulfilled = CollegeAdmissionSeatAllocationHorizontalCategory::query()
                    ->where('reservation_category_id', $categoryId)
                    ->where('fulfills_target', true)
                    ->whereHas('allocation', function ($query) use ($context, $ignoreAllocationId) {
                        $query->where('college_program_intake_id', $context['rule']->college_program_intake_id)
                            ->where('bucket_key', $context['rule']->bucket_key)
                            ->where('status', 'ALLOCATED');
                        if ($ignoreAllocationId) {
                            $query->where('id', '<>', $ignoreAllocationId);
                        }
                    })
                    ->count();

                if ($fulfilled >= (int) $quota->seat_capacity) {
                    throw ValidationException::withMessages([
                        'horizontal_target_category_ids' => $quota->category->name.' target is already fulfilled. Record the candidate as applicable without marking another required target fulfilment.',
                    ]);
                }
            }

            $rows[] = [
                'category_id' => $quota->category->id,
                'category_code' => $quota->category->code,
                'category_name' => $quota->category->name,
                'fulfills_target' => $fulfillsTarget,
            ];
        }

        return $rows;
    }

    private function capacitySnapshot(College $college, CollegeAdmissionSelectionRule $rule): array
    {
        $rule->loadMissing('reservationPlan.allocations.category');
        $context = $this->readSeatContext($college, $rule);
        $allocations = CollegeAdmissionSeatAllocation::query()
            ->where('college_program_intake_id', $rule->college_program_intake_id)
            ->where('bucket_key', $rule->bucket_key)
            ->where('status', 'ALLOCATED')
            ->get();

        $openUsed = $allocations->whereNull('physical_reservation_category_id')->count();
        $vertical = $context['vertical']->map(function ($quota) use ($allocations) {
            $used = $allocations->where('physical_reservation_category_id', $quota->reservation_category_id)->count();
            return [
                'category_id' => $quota->category->id,
                'code' => $quota->category->code,
                'name' => $quota->category->name,
                'capacity' => (int) $quota->seat_capacity,
                'used' => $used,
                'remaining' => max(0, (int) $quota->seat_capacity - $used),
            ];
        })->values();

        $horizontal = $context['horizontal']->map(function ($quota) use ($rule) {
            $actual = CollegeAdmissionSeatAllocationHorizontalCategory::query()
                ->where('reservation_category_id', $quota->reservation_category_id)
                ->whereHas('allocation', fn ($query) => $query
                    ->where('college_program_intake_id', $rule->college_program_intake_id)
                    ->where('bucket_key', $rule->bucket_key)
                    ->where('status', 'ALLOCATED'))
                ->count();
            $fulfilled = CollegeAdmissionSeatAllocationHorizontalCategory::query()
                ->where('reservation_category_id', $quota->reservation_category_id)
                ->where('fulfills_target', true)
                ->whereHas('allocation', fn ($query) => $query
                    ->where('college_program_intake_id', $rule->college_program_intake_id)
                    ->where('bucket_key', $rule->bucket_key)
                    ->where('status', 'ALLOCATED'))
                ->count();

            return [
                'category_id' => $quota->category->id,
                'code' => $quota->category->code,
                'name' => $quota->category->name,
                'target' => (int) $quota->seat_capacity,
                'fulfilled' => $fulfilled,
                'remaining_target' => max(0, (int) $quota->seat_capacity - $fulfilled),
                'actual_candidates' => $actual,
            ];
        })->values();

        return [
            'basis_capacity' => (int) $context['basis_capacity'],
            'total_used' => $allocations->count(),
            'open' => [
                'capacity' => (int) $context['open_capacity'],
                'used' => $openUsed,
                'remaining' => max(0, (int) $context['open_capacity'] - $openUsed),
            ],
            'vertical' => $vertical->all(),
            'horizontal' => $horizontal->all(),
            'has_reservation_plan' => $context['plan'] !== null,
            'reservation_plan_id' => $context['plan']?->id,
            'reservation_plan_status' => $context['plan']?->status,
        ];
    }

    private function readSeatContext(College $college, CollegeAdmissionSelectionRule $rule): array
    {
        $plan = $rule->reservationPlan;
        $vertical = collect();
        $horizontal = collect();

        if ($plan) {
            $plan->loadMissing(['intake.offering', 'allocations.category']);
            abort_unless((int) $plan->intake?->offering?->college_id === (int) $college->id, 404);
            $active = $plan->allocations->where('status', 'ACTIVE')->filter(fn ($row) => $row->category);
            $vertical = $active->filter(fn ($row) => $row->category->nature === 'VERTICAL')->values();
            $horizontal = $active->filter(fn ($row) => $row->category->nature === 'HORIZONTAL')->values();
        }

        $basis = (int) $rule->basis_capacity;
        return [
            'rule' => $rule,
            'plan' => $plan,
            'basis_capacity' => $basis,
            'open_capacity' => max(0, $basis - (int) $vertical->sum('seat_capacity')),
            'vertical' => $vertical,
            'horizontal' => $horizontal,
        ];
    }

    private function assertRuleOwnedByCollege(College $college, CollegeAdmissionSelectionRule $rule): void
    {
        $rule->loadMissing('intake.offering');
        abort_unless((int) $rule->intake?->offering?->college_id === (int) $college->id, 404);
    }

    private function assertMeritOwnedByCollege(College $college, CollegeAdmissionMeritEntry $merit): void
    {
        abort_unless((int) $merit->college_id === (int) $college->id, 404);
    }

    private function assertAllocationOwnedByCollege(College $college, CollegeAdmissionSeatAllocation $allocation): void
    {
        abort_unless((int) $allocation->college_id === (int) $college->id, 404);
    }

    private function audit(
        string $event,
        CollegeAdmissionSeatAllocation $allocation,
        College $college,
        int $actorId,
        ?string $ip,
        ?array $before,
        array $after
    ): void {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => $event,
            'resource_type' => 'CollegeAdmissionSeatAllocation',
            'resource_id' => $allocation->id,
            'scope_type' => 'COLLEGE',
            'scope_reference' => 'college:'.$college->id,
            'before' => $before ? json_encode($before) : null,
            'after' => json_encode($after),
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
