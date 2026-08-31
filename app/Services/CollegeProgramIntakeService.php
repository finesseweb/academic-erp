<?php

namespace App\Services;

use App\Models\College;
use App\Models\CollegeProgramIntake;
use App\Models\CollegeProgramIntakeAllocation;
use App\Models\CollegeProgramOffering;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CollegeProgramIntakeService
{
    public function __construct(
        private EffectiveCurriculumScopeService $effectiveScope,
    ) {
    }

    public function create(
        College $college,
        array $data,
        int $actorId,
        ?string $ip
    ): CollegeProgramIntake {
        $this->assertCollegeActive($college);

        $offering = $this->validOffering(
            $college,
            (int) $data['college_program_offering_id']
        );

        if (
            CollegeProgramIntake::query()
                ->where('college_program_offering_id', $offering->id)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'college_program_offering_id' =>
                    'Intake / Seat Capacity already exists for this Program Offering.',
            ]);
        }

        return DB::transaction(function () use (
            $data,
            $offering,
            $actorId,
            $ip,
            $college
        ) {
            $intake = CollegeProgramIntake::create([
                'college_program_offering_id' => $offering->id,
                'approved_capacity' => (int) $data['approved_capacity'],
                'allocation_mode' => $data['allocation_mode'],
                'status' => 'INACTIVE',
                'notes' => $data['notes'] ?? null,
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit(
                'COLLEGE_PROGRAM_INTAKE_CREATED',
                $intake,
                $college,
                $actorId,
                $ip,
                null,
                $intake->toArray()
            );

            return $intake;
        });
    }

    public function update(
        CollegeProgramIntake $intake,
        College $college,
        array $data,
        int $actorId,
        ?string $ip
    ): CollegeProgramIntake {
        $this->assertOwned($intake, $college);
        $this->assertCollegeActive($college);
        $this->assertEditable($intake);
        $this->assertNoReservationPlans($intake);

        if (
            $data['allocation_mode'] === 'PROGRAM' &&
            $intake->allocations()->exists()
        ) {
            throw ValidationException::withMessages([
                'allocation_mode' =>
                    'Remove all discipline/specialization allocations before switching to Program-only capacity.',
            ]);
        }

        if (
            $data['allocation_mode'] === 'PROGRAM' &&
            $intake->allocations()->exists()
        ) {
            throw ValidationException::withMessages([
                'allocation_mode' =>
                    'Remove all Discipline/Specialization allocations before switching to Program-level capacity.',
            ]);
        }

        if ($data['allocation_mode'] === 'DISCIPLINE') {
            /*
             * Program approved capacity is composed only from the
             * top-level Discipline capacities.
             *
             * Specialization allocations are child buckets INSIDE their
             * parent Discipline capacity and must never be added again.
             *
             * Example:
             * English = 60
             *   Literature = 20
             *   Linguistics = 20
             *
             * Program contribution from English is still 60, not 100.
             */
            $allocated = (int) $intake->allocations()
                ->where('status', 'ACTIVE')
                ->where('seat_scope_type', 'DISCIPLINE')
                ->whereNull('parent_allocation_id')
                ->sum('seat_capacity');

            if ($allocated > (int) $data['approved_capacity']) {
                throw ValidationException::withMessages([
                    'approved_capacity' =>
                        'Approved capacity cannot be lower than the currently allocated Discipline capacity.',
                ]);
            }
        }

        $before = $intake->toArray();

        $intake->update([
            'approved_capacity' => (int) $data['approved_capacity'],
            'allocation_mode' => $data['allocation_mode'],
            'notes' => $data['notes'] ?? null,
            'updated_by' => $actorId,
        ]);

        $this->audit(
            'COLLEGE_PROGRAM_INTAKE_UPDATED',
            $intake,
            $college,
            $actorId,
            $ip,
            $before,
            $intake->fresh()->toArray()
        );

        return $intake->fresh();
    }

    public function addAllocation(
        CollegeProgramIntake $intake,
        College $college,
        array $data,
        int $actorId,
        ?string $ip
    ): CollegeProgramIntakeAllocation {
        $this->assertOwned($intake, $college);
        $this->assertCollegeActive($college);
        $this->assertEditable($intake);
        $this->assertNoReservationPlans($intake);
        $this->assertAllocationModeAllowsRows($intake);
        $this->validateAllocationReference($intake, $data);
        $this->assertAllocationUnique($intake, $data);
        $this->assertCapacityWillFit(
            $intake,
            $data
        );

        $nextOrder = ((int) $intake->allocations()->max('display_order')) + 1;

        return DB::transaction(function () use (
            $intake,
            $college,
            $data,
            $actorId,
            $ip,
            $nextOrder
        ) {
            $allocation = CollegeProgramIntakeAllocation::create([
                'college_program_intake_id' => $intake->id,
                'parent_allocation_id' =>
                    ! empty($data['parent_allocation_id'])
                        ? (int) $data['parent_allocation_id']
                        : null,
                'discipline_id' => (int) $data['discipline_id'],
                'specialization_id' =>
                    ! empty($data['specialization_id'])
                        ? (int) $data['specialization_id']
                        : null,
                'seat_scope_type' => $data['seat_scope_type'],
                'seat_capacity' => (int) $data['seat_capacity'],
                'display_order' => max($nextOrder, 1),
                'status' => 'ACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit(
                'COLLEGE_PROGRAM_INTAKE_ALLOCATION_CREATED',
                $intake,
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
        CollegeProgramIntakeAllocation $allocation,
        CollegeProgramIntake $intake,
        College $college,
        array $data,
        int $actorId,
        ?string $ip
    ): CollegeProgramIntakeAllocation {
        $this->assertAllocationOwned($allocation, $intake);
        $this->assertOwned($intake, $college);
        $this->assertCollegeActive($college);
        $this->assertEditable($intake);
        $this->assertNoReservationPlans($intake);
        $this->assertAllocationModeAllowsRows($intake);
        $this->validateAllocationReference($intake, $data);
        $this->assertAllocationUnique(
            $intake,
            $data,
            $allocation->id
        );

        $this->assertCapacityWillFit(
            $intake,
            $data,
            $allocation->id
        );

        $before = $allocation->toArray();

        $allocation->update([
            'parent_allocation_id' =>
                ! empty($data['parent_allocation_id'])
                    ? (int) $data['parent_allocation_id']
                    : null,
            'discipline_id' => (int) $data['discipline_id'],
            'specialization_id' =>
                ! empty($data['specialization_id'])
                    ? (int) $data['specialization_id']
                    : null,
            'seat_scope_type' => $data['seat_scope_type'],
            'seat_capacity' => (int) $data['seat_capacity'],
            'updated_by' => $actorId,
        ]);

        $this->audit(
            'COLLEGE_PROGRAM_INTAKE_ALLOCATION_UPDATED',
            $intake,
            $college,
            $actorId,
            $ip,
            $before,
            $allocation->fresh()->toArray()
        );

        return $allocation->fresh();
    }

    public function deleteAllocation(
        CollegeProgramIntakeAllocation $allocation,
        CollegeProgramIntake $intake,
        College $college,
        int $actorId,
        ?string $ip
    ): void {
        $this->assertAllocationOwned($allocation, $intake);
        $this->assertOwned($intake, $college);
        $this->assertCollegeActive($college);
        $this->assertEditable($intake);
        $this->assertNoReservationPlans($intake);

        DB::transaction(function () use (
            $allocation,
            $intake,
            $college,
            $actorId,
            $ip
        ) {
            $before = $allocation->toArray();
            $allocationId = $allocation->id;
            $allocation->delete();

            $this->audit(
                'COLLEGE_PROGRAM_INTAKE_ALLOCATION_DELETED',
                $intake,
                $college,
                $actorId,
                $ip,
                $before,
                ['deleted_allocation_id' => $allocationId]
            );
        });
    }

    public function changeStatus(
        CollegeProgramIntake $intake,
        College $college,
        string $status,
        int $actorId,
        ?string $ip
    ): void {
        $this->assertOwned($intake, $college);
        $this->assertCollegeActive($college);

        if (
            $status === 'INACTIVE' &&
            DB::table('college_program_reservation_plans')
                ->where('college_program_intake_id', $intake->id)
                ->where('status', 'ACTIVE')
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'intake' =>
                    'Deactivate all active Reservation / Seat Distribution plans before deactivating this Intake.',
            ]);
        }

        if ($status === 'ACTIVE') {
            $this->validOffering(
                $college,
                $intake->college_program_offering_id
            );
            $this->assertReadyForActivation($intake);
        }

        $before = ['status' => $intake->status];

        $intake->update([
            'status' => $status,
            'updated_by' => $actorId,
        ]);

        $this->audit(
            $status === 'ACTIVE'
                ? 'COLLEGE_PROGRAM_INTAKE_ACTIVATED'
                : 'COLLEGE_PROGRAM_INTAKE_DEACTIVATED',
            $intake,
            $college,
            $actorId,
            $ip,
            $before,
            ['status' => $status]
        );
    }

    private function validOffering(
        College $college,
        int $offeringId
    ): CollegeProgramOffering {
        $offering = CollegeProgramOffering::query()
            ->whereKey($offeringId)
            ->where('college_id', $college->id)
            ->where('status', 'ACTIVE')
            ->first();

        if (! $offering) {
            throw ValidationException::withMessages([
                'college_program_offering_id' =>
                    'Select an ACTIVE Program Offering owned by this College.',
            ]);
        }

        return $offering;
    }

    private function validateAllocationReference(
        CollegeProgramIntake $intake,
        array $data
    ): void {
        $intake->loadMissing('offering.curriculum');

        if ($intake->allocation_mode !== 'DISCIPLINE') {
            throw ValidationException::withMessages([
                'allocation_mode' =>
                    'Child seat allocations are available only for a Discipline-wise Intake.',
            ]);
        }

        $discipline = $this->effectiveScope->discipline(
            $intake->offering,
            (int) $data['discipline_id']
        );

        if (! $discipline) {
            throw ValidationException::withMessages([
                'discipline_id' =>
                    'This Discipline has no active Course / Paper mapping in the current Program Offering Curriculum.',
            ]);
        }

        if ($data['seat_scope_type'] === 'DISCIPLINE') {
            if (! empty($data['parent_allocation_id'])) {
                throw ValidationException::withMessages([
                    'parent_allocation_id' =>
                        'A Discipline seat allocation cannot have a parent allocation.',
                ]);
            }

            if (! empty($data['specialization_id'])) {
                throw ValidationException::withMessages([
                    'specialization_id' =>
                        'Do not select a Specialization for a Discipline seat row.',
                ]);
            }

            return;
        }

        if ($data['seat_scope_type'] !== 'ADMISSION_SPECIALIZATION') {
            throw ValidationException::withMessages([
                'seat_scope_type' => 'Invalid seat allocation type.',
            ]);
        }

        if (empty($data['parent_allocation_id'])) {
            throw ValidationException::withMessages([
                'parent_allocation_id' =>
                    'Select the parent Discipline capacity for this Specialization.',
            ]);
        }

        $parent = $intake->allocations()
            ->whereKey((int) $data['parent_allocation_id'])
            ->where('seat_scope_type', 'DISCIPLINE')
            ->where('discipline_id', (int) $data['discipline_id'])
            ->first();

        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_allocation_id' =>
                    'Selected parent must be a Discipline allocation from this Intake.',
            ]);
        }

        if (empty($data['specialization_id'])) {
            throw ValidationException::withMessages([
                'specialization_id' =>
                    'Select a Specialization that is actually used by the current Curriculum.',
            ]);
        }

        if (! $this->effectiveScope->specialization(
            $intake->offering,
            (int) $data['discipline_id'],
            (int) $data['specialization_id']
        )) {
            throw ValidationException::withMessages([
                'specialization_id' =>
                    'This Specialization has no active Course / Paper mapping for the selected Discipline in the current Curriculum.',
            ]);
        }
    }

    private function assertAllocationUnique(
        CollegeProgramIntake $intake,
        array $data,
        ?int $ignoreId = null
    ): void {
        $query = $intake->allocations()
            ->where('seat_scope_type', $data['seat_scope_type'])
            ->where(
                'discipline_id',
                (int) $data['discipline_id']
            );

        if ($data['seat_scope_type'] === 'DISCIPLINE') {
            $query->whereNull('specialization_id')
                ->whereNull('parent_allocation_id');
        } else {
            $query
                ->where(
                    'parent_allocation_id',
                    (int) $data['parent_allocation_id']
                )
                ->where(
                    'specialization_id',
                    (int) $data['specialization_id']
                );
        }

        if ($ignoreId) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'discipline_id' =>
                    'This seat allocation already exists for the Intake.',
            ]);
        }
    }

    private function assertCapacityWillFit(
        CollegeProgramIntake $intake,
        array $data,
        ?int $ignoreAllocationId = null
    ): void {
        $seatCapacity = (int) $data['seat_capacity'];

        if ($data['seat_scope_type'] === 'DISCIPLINE') {
            $query = $intake->allocations()
                ->where('seat_scope_type', 'DISCIPLINE')
                ->where('status', 'ACTIVE');

            if ($ignoreAllocationId) {
                $query->whereKeyNot($ignoreAllocationId);
            }

            $otherDisciplineSeats =
                (int) $query->sum('seat_capacity');

            if (
                $otherDisciplineSeats + $seatCapacity >
                $intake->approved_capacity
            ) {
                throw ValidationException::withMessages([
                    'seat_capacity' =>
                        'Discipline capacities cannot exceed the approved Program capacity.',
                ]);
            }

            return;
        }

        $parent = $intake->allocations()
            ->whereKey((int) $data['parent_allocation_id'])
            ->where('seat_scope_type', 'DISCIPLINE')
            ->firstOrFail();

        $query = $intake->allocations()
            ->where(
                'parent_allocation_id',
                $parent->id
            )
            ->where(
                'seat_scope_type',
                'ADMISSION_SPECIALIZATION'
            )
            ->where('status', 'ACTIVE');

        if ($ignoreAllocationId) {
            $query->whereKeyNot($ignoreAllocationId);
        }

        $otherSpecializationSeats =
            (int) $query->sum('seat_capacity');

        if (
            $otherSpecializationSeats + $seatCapacity >
            (int) $parent->seat_capacity
        ) {
            throw ValidationException::withMessages([
                'seat_capacity' =>
                    'Specialization capacities cannot exceed the parent Discipline capacity.',
            ]);
        }
    }

    private function assertReadyForActivation(
        CollegeProgramIntake $intake
    ): void {
        if ($intake->approved_capacity < 1) {
            throw ValidationException::withMessages([
                'approved_capacity' =>
                    'Approved capacity must be greater than zero.',
            ]);
        }

        if ($intake->allocation_mode === 'PROGRAM') {
            if ($intake->allocations()->exists()) {
                throw ValidationException::withMessages([
                    'allocation_mode' =>
                        'Program-level Intake cannot contain child seat allocations.',
                ]);
            }

            return;
        }

        $disciplines = $intake->allocations()
            ->where('seat_scope_type', 'DISCIPLINE')
            ->where('status', 'ACTIVE')
            ->get();

        if ($disciplines->isEmpty()) {
            throw ValidationException::withMessages([
                'allocations' =>
                    'Add at least one Discipline capacity before activating a Discipline-wise Intake.',
            ]);
        }

        $intake->loadMissing('offering.curriculum');

        foreach ($disciplines as $discipline) {
            if (! $this->effectiveScope->discipline(
                $intake->offering,
                (int) $discipline->discipline_id
            )) {
                throw ValidationException::withMessages([
                    'allocations' =>
                        "Discipline allocation #{$discipline->id} is no longer used by the current Curriculum. Remove it before activating this Intake.",
                ]);
            }

            $specializationRows = $intake->allocations()
                ->where('parent_allocation_id', $discipline->id)
                ->where('seat_scope_type', 'ADMISSION_SPECIALIZATION')
                ->where('status', 'ACTIVE')
                ->get();

            foreach ($specializationRows as $specializationRow) {
                if (! $this->effectiveScope->specialization(
                    $intake->offering,
                    (int) $discipline->discipline_id,
                    (int) $specializationRow->specialization_id
                )) {
                    throw ValidationException::withMessages([
                        'allocations' =>
                            "Specialization allocation #{$specializationRow->id} is no longer used by the current Curriculum. Remove it before activating this Intake.",
                    ]);
                }
            }
        }

        $disciplineTotal =
            (int) $disciplines->sum('seat_capacity');

        if (
            $disciplineTotal !==
            (int) $intake->approved_capacity
        ) {
            throw ValidationException::withMessages([
                'allocations' =>
                    "Discipline capacities must total exactly {$intake->approved_capacity} seats before activation. Current total: {$disciplineTotal}.",
            ]);
        }

        foreach ($disciplines as $discipline) {
            $specializationTotal =
                (int) $intake->allocations()
                    ->where(
                        'parent_allocation_id',
                        $discipline->id
                    )
                    ->where(
                        'seat_scope_type',
                        'ADMISSION_SPECIALIZATION'
                    )
                    ->where('status', 'ACTIVE')
                    ->sum('seat_capacity');

            if (
                $specializationTotal >
                (int) $discipline->seat_capacity
            ) {
                throw ValidationException::withMessages([
                    'allocations' =>
                        "Specialization capacities under Discipline allocation #{$discipline->id} exceed its capacity.",
                ]);
            }
        }
    }

    private function assertAllocationModeAllowsRows(
        CollegeProgramIntake $intake
    ): void {
        if ($intake->allocation_mode === 'PROGRAM') {
            throw ValidationException::withMessages([
                'allocation_mode' =>
                    'Program-level Intake does not use child seat allocations.',
            ]);
        }
    }

    private function assertEditable(
        CollegeProgramIntake $intake
    ): void {
        if ($intake->status !== 'INACTIVE') {
            throw ValidationException::withMessages([
                'intake' =>
                    'Deactivate the Intake before changing capacity or seat allocations.',
            ]);
        }
    }

    private function assertCollegeActive(College $college): void
    {
        if ($college->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'college' =>
                    'College Academic Setup cannot be changed while this College is inactive.',
            ]);
        }
    }

    private function assertOwned(
        CollegeProgramIntake $intake,
        College $college
    ): void {
        $intake->loadMissing('offering');

        abort_unless(
            (int) $intake->offering?->college_id === (int) $college->id,
            404
        );
    }

    private function assertAllocationOwned(
        CollegeProgramIntakeAllocation $allocation,
        CollegeProgramIntake $intake
    ): void {
        abort_unless(
            (int) $allocation->college_program_intake_id ===
                (int) $intake->id,
            404
        );
    }

    private function assertNoReservationPlans(
        CollegeProgramIntake $intake
    ): void {
        if (
            DB::table('college_program_reservation_plans')
                ->where('college_program_intake_id', $intake->id)
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'intake' =>
                    'Reservation / Seat Distribution already exists for this Intake. Clean the dependent Reservation test data before changing Intake capacity or allocation mode.',
            ]);
        }
    }

    private function assertAllocationNotReserved(
        CollegeProgramIntakeAllocation $allocation,
        CollegeProgramIntake $intake
    ): void {
        $direct = DB::table('college_program_reservation_plans')
            ->where('college_program_intake_id', $intake->id)
            ->where(function ($query) use ($allocation) {
                $query
                    ->where('discipline_allocation_id', $allocation->id)
                    ->orWhere('specialization_allocation_id', $allocation->id);
            })
            ->exists();

        $childIds = $intake->allocations()
            ->where('parent_allocation_id', $allocation->id)
            ->pluck('id');

        $childReferenced = $childIds->isNotEmpty() &&
            DB::table('college_program_reservation_plans')
                ->where('college_program_intake_id', $intake->id)
                ->whereIn('specialization_allocation_id', $childIds)
                ->exists();

        if ($direct || $childReferenced) {
            throw ValidationException::withMessages([
                'allocation' =>
                    'This seat allocation already has Reservation / Seat Distribution data. Clean the dependent Reservation test data first.',
            ]);
        }
    }

    private function audit(
        string $event,
        CollegeProgramIntake $intake,
        College $college,
        int $actorId,
        ?string $ip,
        ?array $before,
        ?array $after
    ): void {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => $event,
            'resource_type' => 'CollegeProgramIntake',
            'resource_id' => $intake->id,
            'scope_type' => 'COLLEGE',
            'scope_reference' => 'college:'.$college->id,
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
