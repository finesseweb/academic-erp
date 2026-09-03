<?php

namespace App\Services;

use App\Models\College;
use App\Models\CollegeProgramIntake;
use App\Models\CollegeProgramReservationAllocation;
use App\Models\CollegeProgramReservationPlan;
use App\Models\CollegeAdmissionSelectionRule;
use App\Models\CollegeAdmissionMeritEntry;
use App\Models\ReservationCategory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CollegeReservationService
{
    public function __construct(
        private EffectiveCurriculumScopeService $effectiveScope,
    ) {
    }

    public function availableBuckets(CollegeProgramIntake $intake): Collection
    {
        $intake->loadMissing([
            'offering.programTemplate:id,name,code',
            'offering.curriculum:id',
            'allocations.discipline:id,name,code',
            'allocations.specialization:id,name,code',
        ]);

        if ($intake->status !== 'ACTIVE') {
            return collect();
        }

        if ($intake->allocation_mode === 'PROGRAM') {
            return collect([[
                'bucket_key' => 'PROGRAM',
                'bucket_type' => 'PROGRAM',
                'label' => $intake->offering->programTemplate->name.' · Program Seat Bucket',
                'basis_capacity' => (int) $intake->approved_capacity,
                'discipline_allocation_id' => null,
                'specialization_allocation_id' => null,
            ]]);
        }

        $all = $intake->allocations()
            ->with([
                'discipline:id,name,code',
                'specialization:id,name,code',
            ])
            ->where('status', 'ACTIVE')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        $disciplines = $all
            ->where('seat_scope_type', 'DISCIPLINE')
            ->whereNull('parent_allocation_id');

        $buckets = collect();

        foreach ($disciplines as $discipline) {
            if (! $this->effectiveScope->discipline(
                $intake->offering,
                (int) $discipline->discipline_id
            )) {
                continue;
            }

            $children = $all
                ->where('parent_allocation_id', $discipline->id)
                ->where('seat_scope_type', 'ADMISSION_SPECIALIZATION')
                ->filter(fn ($child) => $child->specialization_id
                    && $this->effectiveScope->specialization(
                        $intake->offering,
                        (int) $discipline->discipline_id,
                        (int) $child->specialization_id
                    ));

            $specializationTotal = (int) $children->sum('seat_capacity');
            $generalRemaining = (int) $discipline->seat_capacity - $specializationTotal;

            if ($generalRemaining > 0) {
                $buckets->push([
                    'bucket_key' => 'DISCIPLINE_GENERAL:'.$discipline->id,
                    'bucket_type' => 'DISCIPLINE_GENERAL',
                    'label' => $discipline->discipline->name.' · General / No Specialization',
                    'basis_capacity' => $generalRemaining,
                    'discipline_allocation_id' => $discipline->id,
                    'specialization_allocation_id' => null,
                ]);
            }

            foreach ($children as $child) {
                $buckets->push([
                    'bucket_key' => 'SPECIALIZATION:'.$child->id,
                    'bucket_type' => 'SPECIALIZATION',
                    'label' => $discipline->discipline->name.' → '.$child->specialization->name,
                    'basis_capacity' => (int) $child->seat_capacity,
                    'discipline_allocation_id' => $discipline->id,
                    'specialization_allocation_id' => $child->id,
                ]);
            }
        }

        return $buckets->values();
    }

    public function createPlan(
        College $college,
        array $data,
        int $actorId,
        ?string $ip
    ): CollegeProgramReservationPlan {
        $this->assertCollegeActive($college);
        $intake = $this->ownedActiveIntake($college, (int) $data['college_program_intake_id']);

        $bucket = $this->availableBuckets($intake)
            ->firstWhere('bucket_key', $data['bucket_key']);

        if (! $bucket) {
            throw ValidationException::withMessages([
                'bucket_key' => 'Select a valid current admission seat bucket from this active Intake.',
            ]);
        }

        if (CollegeProgramReservationPlan::query()
            ->where('college_program_intake_id', $intake->id)
            ->where('bucket_key', $bucket['bucket_key'])
            ->exists()) {
            throw ValidationException::withMessages([
                'bucket_key' => 'Reservation / Seat Distribution is already configured for this seat bucket.',
            ]);
        }

        return DB::transaction(function () use ($college, $intake, $bucket, $data, $actorId, $ip) {
            $plan = CollegeProgramReservationPlan::create([
                'college_program_intake_id' => $intake->id,
                'bucket_type' => $bucket['bucket_type'],
                'bucket_key' => $bucket['bucket_key'],
                'discipline_allocation_id' => $bucket['discipline_allocation_id'],
                'specialization_allocation_id' => $bucket['specialization_allocation_id'],
                'basis_capacity' => $bucket['basis_capacity'],
                'status' => 'INACTIVE',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit(
                'COLLEGE_RESERVATION_PLAN_CREATED',
                $plan,
                $college,
                $actorId,
                $ip,
                null,
                $plan->toArray()
            );

            return $plan;
        });
    }

    public function updatePlan(
        CollegeProgramReservationPlan $plan,
        College $college,
        array $data,
        int $actorId,
        ?string $ip
    ): CollegeProgramReservationPlan {
        $this->assertOwnedPlan($plan, $college);
        $this->assertPlanEditable($plan);

        $before = $plan->only(['notes', 'status']);

        $plan->update([
            'notes' => $data['notes'] ?? null,
            'updated_by' => $actorId,
        ]);

        $this->audit(
            'COLLEGE_RESERVATION_PLAN_UPDATED',
            $plan,
            $college,
            $actorId,
            $ip,
            $before,
            $plan->fresh()->only(['notes', 'status'])
        );

        return $plan->fresh();
    }

    public function addAllocation(
        CollegeProgramReservationPlan $plan,
        College $college,
        array $data,
        int $actorId,
        ?string $ip
    ): CollegeProgramReservationAllocation {
        $this->assertOwnedPlan($plan, $college);
        $this->assertPlanEditable($plan);

        $category = $this->validCategory($college, (int) $data['reservation_category_id']);
        $this->assertCategoryUnique($plan, $category->id);
        $this->assertAllocationFits($plan, $category, (int) $data['seat_capacity']);

        $nextOrder = ((int) $plan->allocations()->max('display_order')) + 1;

        return DB::transaction(function () use ($plan, $college, $category, $data, $actorId, $ip, $nextOrder) {
            $allocation = CollegeProgramReservationAllocation::create([
                'college_program_reservation_plan_id' => $plan->id,
                'reservation_category_id' => $category->id,
                'seat_capacity' => (int) $data['seat_capacity'],
                'display_order' => max($nextOrder, 1),
                'status' => 'ACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit(
                'COLLEGE_RESERVATION_ALLOCATION_CREATED',
                $plan,
                $college,
                $actorId,
                $ip,
                null,
                $allocation->toArray()
            );

            return $allocation;
        });
    }

    public function updateAllocation(
        CollegeProgramReservationAllocation $allocation,
        CollegeProgramReservationPlan $plan,
        College $college,
        array $data,
        int $actorId,
        ?string $ip
    ): CollegeProgramReservationAllocation {
        $this->assertOwnedAllocation($allocation, $plan);
        $this->assertOwnedPlan($plan, $college);
        $this->assertPlanEditable($plan);

        $category = $this->validCategory($college, (int) $data['reservation_category_id']);
        $this->assertCategoryUnique($plan, $category->id, $allocation->id);
        $this->assertAllocationFits(
            $plan,
            $category,
            (int) $data['seat_capacity'],
            $allocation->id
        );

        $before = $allocation->toArray();

        $allocation->update([
            'reservation_category_id' => $category->id,
            'seat_capacity' => (int) $data['seat_capacity'],
            'updated_by' => $actorId,
        ]);

        $this->audit(
            'COLLEGE_RESERVATION_ALLOCATION_UPDATED',
            $plan,
            $college,
            $actorId,
            $ip,
            $before,
            $allocation->fresh()->toArray()
        );

        return $allocation->fresh();
    }

    public function deleteAllocation(
        CollegeProgramReservationAllocation $allocation,
        CollegeProgramReservationPlan $plan,
        College $college,
        int $actorId,
        ?string $ip
    ): void {
        $this->assertOwnedAllocation($allocation, $plan);
        $this->assertOwnedPlan($plan, $college);
        $this->assertPlanEditable($plan);

        DB::transaction(function () use ($allocation, $plan, $college, $actorId, $ip) {
            $before = $allocation->toArray();
            $id = $allocation->id;
            $allocation->delete();

            $this->audit(
                'COLLEGE_RESERVATION_ALLOCATION_DELETED',
                $plan,
                $college,
                $actorId,
                $ip,
                $before,
                ['deleted_allocation_id' => $id]
            );
        });
    }

    public function changeStatus(
        CollegeProgramReservationPlan $plan,
        College $college,
        string $status,
        int $actorId,
        ?string $ip
    ): void {
        $this->assertOwnedPlan($plan, $college);
        $this->assertCollegeActive($college);

        if ($status === 'ACTIVE') {
            $intake = $this->ownedActiveIntake($college, $plan->college_program_intake_id);
            $currentBucket = $this->availableBuckets($intake)
                ->firstWhere('bucket_key', $plan->bucket_key);

            if (! $currentBucket) {
                throw ValidationException::withMessages([
                    'plan' => 'This admission seat bucket no longer exists in the active Intake.',
                ]);
            }

            if ((int) $currentBucket['basis_capacity'] !== (int) $plan->basis_capacity) {
                throw ValidationException::withMessages([
                    'plan' => 'Seat capacity changed after this Reservation plan was created. Clean/recreate the test plan against the current Intake bucket.',
                ]);
            }

            if (! $plan->allocations()->exists()) {
                throw ValidationException::withMessages([
                    'allocations' => 'Add at least one Reservation / Quota allocation before activation.',
                ]);
            }

            $this->validateCurrentAllocationTotals($plan);
        }

        if ($status === 'ACTIVE') {
            $activeOpenRule = CollegeAdmissionSelectionRule::query()
                ->where('college_program_intake_id', $plan->college_program_intake_id)
                ->where('bucket_key', $plan->bucket_key)
                ->whereNull('college_program_reservation_plan_id')
                ->where('status', 'ACTIVE')
                ->exists();

            if ($activeOpenRule) {
                throw ValidationException::withMessages([
                    'plan' => 'An ACTIVE Selection Rule currently treats this bucket as Open/General because Reservation was not defined. Retire that rule, activate this Reservation plan, then create/activate the next Selection Rule version.',
                ]);
            }
        }

        if ($status === 'INACTIVE') {
            $activeDependentRule = CollegeAdmissionSelectionRule::query()
                ->where('college_program_reservation_plan_id', $plan->id)
                ->where('status', 'ACTIVE')
                ->exists();

            if ($activeDependentRule) {
                throw ValidationException::withMessages([
                    'plan' => 'This Reservation plan is used by an ACTIVE Selection Rule. Retire the Selection Rule before deactivating the Reservation plan.',
                ]);
            }

            $generatedRosterDependsOnPlan = CollegeAdmissionMeritEntry::query()
                ->whereHas('selectionRule', fn ($query) => $query->where('college_program_reservation_plan_id', $plan->id))
                ->exists();

            if ($generatedRosterDependsOnPlan) {
                throw ValidationException::withMessages([
                    'plan' => 'This Reservation plan is locked by a generated Merit / Roster and must remain unchanged through Seat Allocation and Admission Confirmation.',
                ]);
            }
        }

        $before = ['status' => $plan->status];
        $plan->update(['status' => $status, 'updated_by' => $actorId]);

        $this->audit(
            $status === 'ACTIVE'
                ? 'COLLEGE_RESERVATION_PLAN_ACTIVATED'
                : 'COLLEGE_RESERVATION_PLAN_DEACTIVATED',
            $plan,
            $college,
            $actorId,
            $ip,
            $before,
            ['status' => $status]
        );
    }

    private function validateCurrentAllocationTotals(CollegeProgramReservationPlan $plan): void
    {
        $allocations = $plan->allocations()
            ->with('category:id,nature')
            ->where('status', 'ACTIVE')
            ->get();

        $vertical = (int) $allocations
            ->filter(fn ($a) => $a->category->nature === 'VERTICAL')
            ->sum('seat_capacity');

        if ($vertical > $plan->basis_capacity) {
            throw ValidationException::withMessages([
                'allocations' => 'Vertical Reservation seats exceed the admission seat bucket capacity.',
            ]);
        }

        foreach ($allocations->filter(fn ($a) => $a->category->nature === 'HORIZONTAL') as $allocation) {
            if ((int) $allocation->seat_capacity > (int) $plan->basis_capacity) {
                throw ValidationException::withMessages([
                    'allocations' => 'A Horizontal quota cannot exceed the admission seat bucket capacity.',
                ]);
            }
        }
    }

    private function assertAllocationFits(
        CollegeProgramReservationPlan $plan,
        ReservationCategory $category,
        int $seatCapacity,
        ?int $ignoreAllocationId = null
    ): void {
        if ($seatCapacity > $plan->basis_capacity) {
            throw ValidationException::withMessages([
                'seat_capacity' => 'Quota capacity cannot exceed the admission seat bucket capacity.',
            ]);
        }

        if ($category->nature === 'HORIZONTAL') {
            return;
        }

        $query = $plan->allocations()
            ->where('status', 'ACTIVE')
            ->whereHas('category', fn ($q) => $q->where('nature', 'VERTICAL'));

        if ($ignoreAllocationId) {
            $query->whereKeyNot($ignoreAllocationId);
        }

        $verticalTotal = (int) $query->sum('seat_capacity');

        if ($verticalTotal + $seatCapacity > $plan->basis_capacity) {
            throw ValidationException::withMessages([
                'seat_capacity' => 'Vertical Reservation allocations cannot exceed the admission seat bucket capacity.',
            ]);
        }
    }

    private function ownedActiveIntake(College $college, int $intakeId): CollegeProgramIntake
    {
        $intake = CollegeProgramIntake::query()
            ->with('offering')
            ->whereKey($intakeId)
            ->where('status', 'ACTIVE')
            ->whereHas('offering', fn ($query) => $query->where('college_id', $college->id))
            ->first();

        if (! $intake) {
            throw ValidationException::withMessages([
                'college_program_intake_id' => 'Select an ACTIVE Intake / Seat Capacity owned by this College.',
            ]);
        }

        return $intake;
    }

    private function validCategory(College $college, int $categoryId): ReservationCategory
    {
        $category = ReservationCategory::query()
            ->whereKey($categoryId)
            ->where('university_id', $college->university_id)
            ->where('status', 'ACTIVE')
            ->first();

        if (! $category) {
            throw ValidationException::withMessages([
                'reservation_category_id' => 'Select an ACTIVE Reservation / Quota category from this University.',
            ]);
        }

        return $category;
    }

    private function assertCategoryUnique(
        CollegeProgramReservationPlan $plan,
        int $categoryId,
        ?int $ignoreId = null
    ): void {
        $query = $plan->allocations()->where('reservation_category_id', $categoryId);

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'reservation_category_id' => 'This Reservation / Quota category is already configured for the seat bucket.',
            ]);
        }
    }

    private function assertPlanEditable(CollegeProgramReservationPlan $plan): void
    {
        if ($plan->status !== 'INACTIVE') {
            throw ValidationException::withMessages([
                'plan' => 'Deactivate the Reservation plan before changing its allocations.',
            ]);
        }

        $generatedRosterDependsOnPlan = CollegeAdmissionMeritEntry::query()
            ->whereHas('selectionRule', fn ($query) => $query->where('college_program_reservation_plan_id', $plan->id))
            ->exists();

        if ($generatedRosterDependsOnPlan) {
            throw ValidationException::withMessages([
                'plan' => 'This Reservation plan is already locked by a generated Merit / Roster. Its seat/quota structure can no longer be edited.',
            ]);
        }
    }

    private function assertOwnedPlan(CollegeProgramReservationPlan $plan, College $college): void
    {
        $plan->loadMissing('intake.offering');

        abort_unless(
            (int) $plan->intake?->offering?->college_id === (int) $college->id,
            404
        );
    }

    private function assertOwnedAllocation(
        CollegeProgramReservationAllocation $allocation,
        CollegeProgramReservationPlan $plan
    ): void {
        abort_unless(
            (int) $allocation->college_program_reservation_plan_id === (int) $plan->id,
            404
        );
    }

    private function assertCollegeActive(College $college): void
    {
        if ($college->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'college' => 'College Academic Setup cannot be changed while this College is inactive.',
            ]);
        }
    }

    private function audit(
        string $event,
        CollegeProgramReservationPlan $plan,
        College $college,
        int $actorId,
        ?string $ip,
        ?array $before,
        ?array $after
    ): void {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => $event,
            'resource_type' => 'CollegeProgramReservationPlan',
            'resource_id' => $plan->id,
            'scope_type' => 'COLLEGE',
            'scope_reference' => 'college:'.$college->id,
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
