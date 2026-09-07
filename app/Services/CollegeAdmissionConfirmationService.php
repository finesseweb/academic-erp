<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\College;
use App\Models\CollegeAdmissionSeatAllocation;
use App\Models\FeeDemand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CollegeAdmissionConfirmationService
{
    public function screen(College $college, ?int $ruleId = null): array
    {
        $query = CollegeAdmissionSeatAllocation::query()
            ->where('college_id', $college->id)
            ->where('status', 'ALLOCATED')
            ->with([
                'application:id,application_no,candidate_name,email,status',
                'choice:id,preference_no',
                'documentVerification:id,college_admission_application_id,status,finalized_at',
                'selectionRule:id,name,code,version_no,college_program_intake_id,bucket_key,basis_capacity',
                'selectionRule.intake.offering.programTemplate.degree.degreeLevel:id,name,code',
                'selectionRule.intake.offering.academicSession:id,name,code,is_current',
                'horizontalCategories:id,college_admission_seat_allocation_id,reservation_category_id,category_code,category_name,fulfills_target',
            ])
            ->orderBy('merit_rank');

        if ($ruleId) {
            $query->where('college_admission_selection_rule_id', $ruleId);
        }

        $allocations = $query->get();
        $admissions = Admission::query()
            ->where('college_id', $college->id)
            ->whereIn('college_admission_seat_allocation_id', $allocations->pluck('id'))
            ->get()
            ->keyBy('college_admission_seat_allocation_id');

        $rows = $allocations->map(function (CollegeAdmissionSeatAllocation $allocation) use ($admissions) {
            $admission = $admissions->get($allocation->id);
            $rule = $allocation->selectionRule;
            $program = $rule?->intake?->offering?->programTemplate;
            $degree = $program?->degree;

            return [
                'seat_allocation_id' => $allocation->id,
                'application_id' => $allocation->college_admission_application_id,
                'application_no' => $allocation->application?->application_no,
                'candidate_name' => $allocation->application?->candidate_name,
                'application_status' => $allocation->application?->status,
                'preference_no' => (int) ($allocation->choice?->preference_no ?? 1),
                'merit_rank' => (int) $allocation->merit_rank,
                'final_weighted_score' => (float) $allocation->final_weighted_score,
                'document_verification_status' => $allocation->documentVerification?->status,
                'physical_seat_type' => $allocation->physical_seat_type,
                'physical_category_code' => $allocation->physical_category_code,
                'physical_category_name' => $allocation->physical_category_name,
                'allocation_round' => (int) $allocation->allocation_round,
                'allocated_at' => optional($allocation->allocated_at)->toIso8601String(),
                'horizontal_categories' => $allocation->horizontalCategories->map(fn ($row) => [
                    'id' => $row->reservation_category_id,
                    'code' => $row->category_code,
                    'name' => $row->category_name,
                    'fulfills_target' => (bool) $row->fulfills_target,
                ])->values()->all(),
                'rule' => $rule ? [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'code' => $rule->code,
                    'version_no' => (int) $rule->version_no,
                    'bucket_key' => $rule->bucket_key,
                    'program_name' => $program?->name,
                    'program_code' => $program?->code,
                    'degree_name' => $degree?->name,
                    'degree_level_name' => $degree?->degreeLevel?->name,
                    'session_name' => $rule->intake?->offering?->academicSession?->name,
                ] : null,
                'admission' => $admission ? [
                    'id' => $admission->id,
                    'admission_no' => $admission->admission_no,
                    'status' => $admission->status,
                    'decision_note' => $admission->decision_note,
                    'confirmed_at' => optional($admission->confirmed_at)->toIso8601String(),
                    'revoked_at' => optional($admission->revoked_at)->toIso8601String(),
                    'revocation_reason' => $admission->revocation_reason,
                ] : null,
            ];
        })->values();

        return [
            'summary' => [
                'allocated_candidates' => $rows->count(),
                'ready_to_confirm' => $rows->filter(fn ($row) => $row['document_verification_status'] === 'VERIFIED' && (! $row['admission'] || $row['admission']['status'] === 'REVOKED'))->count(),
                'confirmed' => $rows->filter(fn ($row) => ($row['admission']['status'] ?? null) === 'CONFIRMED')->count(),
                'revoked' => $rows->filter(fn ($row) => ($row['admission']['status'] ?? null) === 'REVOKED')->count(),
            ],
            'rows' => $rows->all(),
        ];
    }

    public function confirm(
        College $college,
        CollegeAdmissionSeatAllocation $allocation,
        ?string $decisionNote,
        int $actorId,
        ?string $ip
    ): Admission {
        $this->assertAllocationOwnedByCollege($college, $allocation);

        return DB::transaction(function () use ($college, $allocation, $decisionNote, $actorId, $ip) {
            $locked = CollegeAdmissionSeatAllocation::query()
                ->whereKey($allocation->id)
                ->lockForUpdate()
                ->with(['application', 'documentVerification', 'intake.offering:id,curriculum_id'])
                ->firstOrFail();

            if ($locked->status !== 'ALLOCATED') {
                throw ValidationException::withMessages([
                    'allocation' => 'Admission can be confirmed only against an ACTIVE seat allocation.',
                ]);
            }

            if (! $locked->documentVerification || $locked->documentVerification->status !== 'VERIFIED') {
                throw ValidationException::withMessages([
                    'verification' => 'Admission Confirmation requires the exact Document Verification linked to this seat allocation to remain VERIFIED.',
                ]);
            }

            if ((int) $locked->documentVerification->id !== (int) $locked->college_admission_document_verification_id) {
                throw ValidationException::withMessages([
                    'verification' => 'The seat allocation verification reference is inconsistent and Admission Confirmation is blocked.',
                ]);
            }

            if (! $locked->application || $locked->application->status !== 'SUBMITTED') {
                throw ValidationException::withMessages([
                    'application' => 'Only a submitted Admission Application can be confirmed.',
                ]);
            }

            $conflictingAdmission = Admission::query()
                ->where('college_admission_application_id', $locked->college_admission_application_id)
                ->where('college_admission_seat_allocation_id', '!=', $locked->id)
                ->where('status', 'CONFIRMED')
                ->lockForUpdate()
                ->first();

            if ($conflictingAdmission) {
                throw ValidationException::withMessages([
                    'application' => 'This application already has a confirmed Admission against another seat allocation.',
                ]);
            }

            $admission = Admission::query()
                ->where('college_admission_seat_allocation_id', $locked->id)
                ->lockForUpdate()
                ->first();

            if ($admission?->status === 'CONFIRMED') {
                return $admission;
            }

            $now = now();
            $values = [
                'college_id' => $college->id,
                'college_program_intake_id' => $locked->college_program_intake_id,
                'college_program_reservation_plan_id' => $locked->college_program_reservation_plan_id,
                'curriculum_id' => $locked->intake?->offering?->curriculum_id,
                'college_admission_application_id' => $locked->college_admission_application_id,
                'college_admission_application_choice_id' => $locked->college_admission_application_choice_id,
                'college_admission_document_verification_id' => $locked->college_admission_document_verification_id,
                'college_admission_seat_allocation_id' => $locked->id,
                'college_admission_merit_entry_id' => $locked->college_admission_merit_entry_id,
                'college_admission_score_id' => $locked->college_admission_score_id,
                'college_admission_selection_rule_id' => $locked->college_admission_selection_rule_id,
                'admission_no' => $admission?->admission_no ?? $this->admissionNumber($college, $locked),
                'status' => 'CONFIRMED',
                'decision_note' => filled($decisionNote) ? trim((string) $decisionNote) : null,
                'confirmed_at' => $now,
                'confirmed_by' => $actorId,
                'revoked_at' => null,
                'revoked_by' => null,
                'revocation_reason' => null,
            ];

            $before = $admission?->toArray();
            if ($admission) {
                $admission->update($values);
                $event = 'COLLEGE_ADMISSION_RECONFIRMED';
            } else {
                $admission = Admission::create($values);
                $event = 'COLLEGE_ADMISSION_CONFIRMED';
            }

            $fresh = $admission->fresh();
            $this->audit($event, $fresh, $college, $actorId, $ip, $before, $fresh->toArray());

            return $fresh;
        });
    }

    public function revoke(
        College $college,
        Admission $admission,
        string $reason,
        int $actorId,
        ?string $ip
    ): void {
        $this->assertAdmissionOwnedByCollege($college, $admission);

        DB::transaction(function () use ($college, $admission, $reason, $actorId, $ip) {
            $locked = Admission::query()->whereKey($admission->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'REVOKED') {
                return;
            }

            if ($this->isConsumedByStudent($locked)) {
                throw ValidationException::withMessages([
                    'admission' => 'This Admission Confirmation is already consumed by Student Enrollment and cannot be revoked here.',
                ]);
            }

            $demands = FeeDemand::query()
                ->where('admission_id', $locked->id)
                ->where('status', '!=', 'CANCELLED')
                ->lockForUpdate()
                ->get();

            if ($demands->contains(fn (FeeDemand $demand) => (float) $demand->paid_amount > 0 || (float) $demand->adjusted_amount > 0)) {
                throw ValidationException::withMessages([
                    'admission' => 'Admission has fee payment/adjustment activity. Reverse or settle that financial activity before revoking the Admission Confirmation.',
                ]);
            }

            foreach ($demands as $demand) {
                $demand->update([
                    'status' => 'CANCELLED',
                    'cancelled_at' => now(),
                    'cancelled_by' => $actorId,
                    'cancellation_reason' => 'Automatically cancelled because Admission Confirmation was revoked.',
                ]);
            }

            $before = $locked->toArray();
            $locked->update([
                'status' => 'REVOKED',
                'revoked_at' => now(),
                'revoked_by' => $actorId,
                'revocation_reason' => trim($reason),
            ]);

            $fresh = $locked->fresh();
            $this->audit('COLLEGE_ADMISSION_CONFIRMATION_REVOKED', $fresh, $college, $actorId, $ip, $before, $fresh->toArray());
        });
    }

    private function admissionNumber(College $college, CollegeAdmissionSeatAllocation $allocation): string
    {
        $code = strtoupper(preg_replace('/[^A-Z0-9]+/i', '', (string) $college->code) ?: 'COL');

        return sprintf('ADM-%s-%s-%d', $code, now()->format('Y'), $allocation->college_admission_application_id);
    }

    private function isConsumedByStudent(Admission $admission): bool
    {
        if (! Schema::hasTable('students')) {
            return false;
        }

        if (Schema::hasColumn('students', 'admission_id')
            && DB::table('students')->where('admission_id', $admission->id)->exists()) {
            return true;
        }

        return Schema::hasColumn('students', 'college_admission_application_id')
            && DB::table('students')->where('college_admission_application_id', $admission->college_admission_application_id)->exists();
    }

    private function assertAllocationOwnedByCollege(College $college, CollegeAdmissionSeatAllocation $allocation): void
    {
        if ((int) $allocation->college_id !== (int) $college->id) {
            abort(404);
        }
    }

    private function assertAdmissionOwnedByCollege(College $college, Admission $admission): void
    {
        if ((int) $admission->college_id !== (int) $college->id) {
            abort(404);
        }
    }

    private function audit(
        string $event,
        Admission $admission,
        College $college,
        int $actorId,
        ?string $ip,
        ?array $before,
        array $after
    ): void {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => $event,
            'resource_type' => 'Admission',
            'resource_id' => $admission->id,
            'scope_type' => 'COLLEGE',
            'scope_reference' => 'college:'.$college->id,
            'before' => $before ? json_encode($before) : null,
            'after' => json_encode($after),
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
