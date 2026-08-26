<?php

namespace App\Services;

use App\Models\College;
use App\Models\CollegeAdmissionCycle;
use App\Models\CollegeProgramOffering;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CollegeAdmissionCycleService
{
    public function create(College $college, array $data, int $actorId, ?string $ip): CollegeAdmissionCycle
    {
        $this->assertCollegeActive($college);
        $offering = $this->validateProgramOffering($college, (int) $data['college_program_offering_id'], $data);

        return DB::transaction(function () use ($college, $offering, $data, $actorId, $ip) {
            unset($data['college_program_offering_id']);
            $cycle = CollegeAdmissionCycle::create([
                ...$data,
                'college_id' => $college->id,
                'college_program_offering_id' => $offering->id,
                // Derived compatibility snapshot. Program Offering remains authoritative.
                'academic_session_id' => $offering->academic_session_id,
                'status' => 'INACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);

            $this->audit('COLLEGE_ADMISSION_CYCLE_CREATED', $cycle, $college, $actorId, $ip, null, $cycle->toArray());
            return $cycle;
        });
    }

    public function update(CollegeAdmissionCycle $cycle, College $college, array $data, int $actorId, ?string $ip): CollegeAdmissionCycle
    {
        $this->assertOwned($cycle, $college);
        $this->assertCollegeActive($college);

        if ($cycle->status === 'CLOSED') {
            throw ValidationException::withMessages(['cycle' => 'A closed Admission Cycle cannot be edited.']);
        }

        if ($cycle->status === 'ACTIVE' && (int) $cycle->college_program_offering_id !== (int) $data['college_program_offering_id']) {
            throw ValidationException::withMessages([
                'college_program_offering_id' => 'Deactivate the Admission Cycle before changing its Program Offering.',
            ]);
        }

        $offering = $this->validateProgramOffering($college, (int) $data['college_program_offering_id'], $data);

        return DB::transaction(function () use ($cycle, $college, $offering, $data, $actorId, $ip) {
            $before = $cycle->toArray();
            unset($data['college_program_offering_id']);
            $cycle->update([
                ...$data,
                'college_program_offering_id' => $offering->id,
                'academic_session_id' => $offering->academic_session_id,
                'updated_by' => $actorId,
            ]);
            $this->audit('COLLEGE_ADMISSION_CYCLE_UPDATED', $cycle, $college, $actorId, $ip, $before, $cycle->fresh()->toArray());
            return $cycle;
        });
    }

    public function changeStatus(CollegeAdmissionCycle $cycle, College $college, string $status, int $actorId, ?string $ip): void
    {
        $this->assertOwned($cycle, $college);
        $this->assertCollegeActive($college);

        if ($cycle->status === 'CLOSED' && $status !== 'CLOSED') {
            throw ValidationException::withMessages(['status' => 'A closed Admission Cycle cannot be reopened.']);
        }

        if ($status === 'ACTIVE') {
            if (! $cycle->college_program_offering_id) {
                throw ValidationException::withMessages([
                    'status' => 'Select an ACTIVE Program Offering for this legacy Admission Cycle before activation.',
                ]);
            }

            $offering = $this->validateProgramOffering($college, (int) $cycle->college_program_offering_id, $cycle->toArray());

            $hasActiveSelectionRule = DB::table('college_admission_selection_rules as sr')
                ->join('college_program_intakes as i', 'i.id', '=', 'sr.college_program_intake_id')
                ->leftJoin('college_program_reservation_plans as rp', 'rp.id', '=', 'sr.college_program_reservation_plan_id')
                ->where('i.college_program_offering_id', $offering->id)
                ->where('i.status', 'ACTIVE')
                ->where('sr.status', 'ACTIVE')
                ->where(function ($query) {
                    $query->whereNull('sr.college_program_reservation_plan_id')
                        ->orWhere('rp.status', 'ACTIVE');
                })
                ->exists();

            if (! $hasActiveSelectionRule) {
                throw ValidationException::withMessages([
                    'status' => 'Activate at least one Merit / Roster / Selection Rule for an eligible seat bucket in this Program Offering before activating its Admission Cycle.',
                ]);
            }
        }

        if ($status === 'INACTIVE' && DB::table('college_admission_applications')
            ->where('college_admission_cycle_id', $cycle->id)
            ->where('status', 'SUBMITTED')
            ->exists()) {
            throw ValidationException::withMessages([
                'status' => 'This Admission Cycle already has submitted applications. Close the cycle instead of deactivating it.',
            ]);
        }

        DB::transaction(function () use ($cycle, $college, $status, $actorId, $ip) {
            $before = ['status' => $cycle->status];
            $cycle->update(['status' => $status, 'updated_by' => $actorId]);
            $event = match ($status) {
                'ACTIVE' => 'COLLEGE_ADMISSION_CYCLE_ACTIVATED',
                'CLOSED' => 'COLLEGE_ADMISSION_CYCLE_CLOSED',
                default => 'COLLEGE_ADMISSION_CYCLE_DEACTIVATED',
            };
            $this->audit($event, $cycle, $college, $actorId, $ip, $before, ['status' => $status]);
        });
    }

    private function validateProgramOffering(College $college, int $offeringId, array $data): CollegeProgramOffering
    {
        $offering = CollegeProgramOffering::query()
            ->with('academicSession:id,university_id,starts_on,ends_on,status')
            ->whereKey($offeringId)
            ->where('college_id', $college->id)
            ->where('status', 'ACTIVE')
            ->first();

        if (! $offering || ! $offering->academicSession || ! in_array($offering->academicSession->status, ['PLANNED', 'ACTIVE'], true)) {
            throw ValidationException::withMessages([
                'college_program_offering_id' => 'Select an ACTIVE Program Offering belonging to this College and a valid Academic Session.',
            ]);
        }

        $start = $data['application_start_date'] ?? null;
        $end = $data['admission_end_date'] ?? null;
        if ($start && $end && ($start < $offering->academicSession->starts_on || $end > $offering->academicSession->ends_on)) {
            throw ValidationException::withMessages([
                'application_start_date' => 'Admission Cycle dates must remain inside the Academic Session of the selected Program Offering.',
            ]);
        }

        return $offering;
    }

    private function assertCollegeActive(College $college): void
    {
        if ($college->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['college' => 'Admission setup cannot be changed while this College is inactive.']);
        }
    }

    private function assertOwned(CollegeAdmissionCycle $cycle, College $college): void
    {
        abort_unless((int) $cycle->college_id === (int) $college->id, 404);
    }

    private function audit(string $event, CollegeAdmissionCycle $cycle, College $college, int $actorId, ?string $ip, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => $event,
            'resource_type' => 'CollegeAdmissionCycle',
            'resource_id' => $cycle->id,
            'scope_type' => 'COLLEGE',
            'scope_reference' => 'college:'.$college->id,
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
