<?php

namespace App\Services;

use App\Models\College;
use App\Models\CollegeAdmissionApplication;
use App\Models\CollegeAdmissionApplicationChoice;
use App\Models\CollegeAdmissionCycle;
use App\Models\CollegeAdmissionSelectionRule;
use App\Models\CollegeProgramIntake;
use App\Models\CollegeProgramReservationPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CollegeAdmissionApplicationService
{
    public function __construct(
        private CollegeReservationService $reservationService,
        private CollegeAdmissionFormResolver $formResolver,
        private CollegeAdmissionDynamicFieldService $dynamicFieldService,
        private ApplicantAcademicPreferenceService $academicPreferenceService,
    ) {
    }

    public function create(College $college, array $data, ?int $actorId, ?string $ip, string $entrySource = 'INTERNAL'): CollegeAdmissionApplication
    {
        $this->assertCollegeActive($college);
        $cycle = $this->activeCycle($college, (int) $data['college_admission_cycle_id']);
        $admissionMode = strtoupper((string) ($data['admission_mode'] ?? 'REGULAR'));
        $this->formResolver->assertAdmissionModeAllowed($college, $cycle, $admissionMode);
        // Application capture must not expose or persist seat-bucket choices.
        // REGULAR processing context is resolved and locked only on Submit from the saved academic preference;
        // DIRECT applications bypass Selection Rule processing and are allocated downstream.
        $contexts = [];
        $academicPreference = $this->academicPreferenceService->resolve($cycle, $data['academic_preference'] ?? []);
        $template = $this->formResolver->resolveTemplate($college, $cycle, $admissionMode);
        $customValues = $template ? $this->dynamicFieldService->validateAndNormalize($template, $cycle, $data['custom_fields'] ?? []) : [];
        $fee = $this->formResolver->resolveFee($college, $cycle);

        return DB::transaction(function () use ($college, $cycle, $data, $contexts, $academicPreference, $actorId, $ip, $template, $customValues, $fee, $admissionMode, $entrySource) {
            $applicantUserId = isset($data['applicant_user_id']) ? (int) $data['applicant_user_id'] : null;
            if ($applicantUserId) {
                // Serialize application creation for one applicant so two browser tabs /
                // concurrent requests cannot create the same active application twice.
                DB::table('users')->where('id', $applicantUserId)->lockForUpdate()->first();

                $existing = CollegeAdmissionApplication::query()
                    ->where('college_id', $college->id)
                    ->where('college_admission_cycle_id', $cycle->id)
                    ->where('applicant_user_id', $applicantUserId)
                    ->whereIn('status', ['DRAFT', 'SUBMITTED'])
                    ->orderByDesc('id')
                    ->first();

                if ($existing) {
                    throw ValidationException::withMessages([
                        'application' => 'You already have an application for this Program Offering in this Admission Cycle ('.$existing->application_no.'). A second application is not allowed.',
                    ]);
                }
            }

            $application = CollegeAdmissionApplication::create([
                'college_id' => $college->id,
                'applicant_user_id' => $data['applicant_user_id'] ?? null,
                'college_admission_cycle_id' => $cycle->id,
                'application_no' => $this->allocateApplicationNumber($college, $cycle),
                'college_admission_form_template_id' => $template?->id,
                'admission_mode' => $admissionMode,
                'entry_source' => $entrySource,
                'application_fee_rule_id' => $fee['rule']?->id,
                'application_fee_required' => $fee['required'],
                'application_fee_amount' => $fee['required'] ? $fee['amount'] : 0,
                'application_fee_currency' => $fee['currency'],
                'form_snapshot' => $this->formResolver->templatePayload($template, $cycle),
                ...$this->candidateData($data),
                'status' => 'DRAFT',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);


            $this->replaceChoices($application, $contexts);
            $this->academicPreferenceService->persist($application, $academicPreference);
            if ($template) $this->dynamicFieldService->persist($application, $template, $customValues);
            $application->load($this->applicationRelations());
            $this->audit('COLLEGE_ADMISSION_APPLICATION_CREATED', $application, $college, $actorId, $ip, null, $application->toArray());

            return $application;
        });
    }

    public function update(CollegeAdmissionApplication $application, College $college, array $data, int $actorId, ?string $ip): CollegeAdmissionApplication
    {
        $this->assertOwned($application, $college);
        $this->assertCollegeActive($college);
        $this->assertDraft($application);

        $cycle = $this->activeCycle($college, (int) $data['college_admission_cycle_id']);
        $admissionMode = strtoupper((string) ($data['admission_mode'] ?? $application->admission_mode ?? 'REGULAR'));
        $this->formResolver->assertAdmissionModeAllowed($college, $cycle, $admissionMode);
        $contexts = [];
        $academicPreference = $this->academicPreferenceService->resolve($cycle, $data['academic_preference'] ?? []);
        $template = $this->formResolver->resolveTemplate($college, $cycle, $admissionMode);
        $customValues = $template ? $this->dynamicFieldService->validateAndNormalize($template, $cycle, $data['custom_fields'] ?? [], $application) : [];
        $fee = $this->formResolver->resolveFee($college, $cycle);

        return DB::transaction(function () use ($application, $college, $cycle, $data, $contexts, $academicPreference, $actorId, $ip, $template, $customValues, $fee, $admissionMode) {
            $application->load('choices');
            $before = $application->toArray();

            $application->update([
                'college_admission_cycle_id' => $cycle->id,
                'college_admission_form_template_id' => $template?->id,
                'admission_mode' => $admissionMode,
                'application_fee_rule_id' => $fee['rule']?->id,
                'application_fee_required' => $fee['required'],
                'application_fee_amount' => $fee['required'] ? $fee['amount'] : 0,
                'application_fee_currency' => $fee['currency'],
                'form_snapshot' => $this->formResolver->templatePayload($template, $cycle),
                ...$this->candidateData($data),
                'updated_by' => $actorId,
            ]);
            $this->replaceChoices($application, $contexts);
            $this->academicPreferenceService->persist($application, $academicPreference);
            if ($template) $this->dynamicFieldService->persist($application, $template, $customValues);
            else $application->fieldValues()->delete();

            $fresh = $application->fresh()->load($this->applicationRelations());
            $this->audit('COLLEGE_ADMISSION_APPLICATION_UPDATED', $fresh, $college, $actorId, $ip, $before, $fresh->toArray());

            return $fresh;
        });
    }

    public function submit(CollegeAdmissionApplication $application, College $college, ?int $actorId, ?string $ip): CollegeAdmissionApplication
    {
        $this->assertOwned($application, $college);
        $this->assertCollegeActive($college);
        $this->assertDraft($application);

        $application->load(['admissionCycle', 'choices']);
        $cycle = $this->activeCycle($college, (int) $application->college_admission_cycle_id);
        $today = today()->toDateString();
        if ($today < $cycle->application_start_date->toDateString() || $today > $cycle->application_end_date->toDateString()) {
            throw ValidationException::withMessages([
                'application' => 'This Admission Cycle is outside its Application Start / End window. A draft cannot be submitted now.',
            ]);
        }

        // New applications are intentionally not bound to seat buckets at application stage.
        // Keep legacy seat-bucket choices valid for older records, but do not require or create them here.
        $choiceInput = $application->choices->map(fn ($choice) => [
            'college_program_intake_id' => $choice->college_program_intake_id,
            'bucket_key' => $choice->bucket_key,
        ])->all();
        $admissionMode = strtoupper((string) ($application->admission_mode ?? 'REGULAR'));
        $contexts = $choiceInput
            ? $this->resolveChoices($college, $cycle, $choiceInput, $admissionMode)
            : ($admissionMode === 'REGULAR'
                ? $this->resolveRegularProcessingContextFromAcademicPreference($application, $college, $cycle)
                : []);

        return DB::transaction(function () use ($application, $college, $contexts, $actorId, $ip) {
            $before = $application->load('choices')->toArray();
            $this->replaceChoices($application, $contexts);
            $application->update([
                'status' => 'SUBMITTED',
                'submitted_at' => now(),
                'withdrawn_at' => null,
                'updated_by' => $actorId,
            ]);

            $fresh = $application->fresh()->load($this->applicationRelations());
            $this->audit('COLLEGE_ADMISSION_APPLICATION_SUBMITTED', $fresh, $college, $actorId, $ip, $before, $fresh->toArray());

            return $fresh;
        });
    }

    public function setEligibility(CollegeAdmissionApplicationChoice $choice, College $college, string $status, ?string $reason, int $actorId, ?string $ip): CollegeAdmissionApplicationChoice
    {
        $application = $choice->application()->firstOrFail();
        $this->assertOwned($application, $college);
        if ($application->status !== 'SUBMITTED') {
            throw ValidationException::withMessages(['eligibility_status' => 'Eligibility can be assessed only for a SUBMITTED application.']);
        }
        if (! in_array($status, ['PENDING', 'ELIGIBLE', 'INELIGIBLE'], true)) {
            throw ValidationException::withMessages(['eligibility_status' => 'Invalid eligibility status.']);
        }
        if ($status === 'INELIGIBLE' && blank($reason)) {
            throw ValidationException::withMessages(['eligibility_reason' => 'Give a reason when a candidate choice is marked INELIGIBLE.']);
        }

        return DB::transaction(function () use ($choice, $application, $college, $status, $reason, $actorId, $ip) {
            $before = $choice->toArray();
            $choice->update([
                'eligibility_status' => $status,
                'eligibility_reason' => $status === 'PENDING' ? null : (filled($reason) ? trim((string) $reason) : null),
                'eligibility_checked_at' => $status === 'PENDING' ? null : now(),
                'eligibility_checked_by' => $status === 'PENDING' ? null : $actorId,
            ]);
            $fresh = $choice->fresh();
            $this->audit('COLLEGE_ADMISSION_CHOICE_ELIGIBILITY_CHANGED', $application, $college, $actorId, $ip, $before, $fresh->toArray());
            return $fresh;
        });
    }

    public function withdraw(CollegeAdmissionApplication $application, College $college, int $actorId, ?string $ip): void
    {
        $this->assertOwned($application, $college);
        if ($application->status === 'WITHDRAWN') {
            return;
        }

        $blockingTables = [
            'college_admission_scores',
            'college_admission_interviews',
            'college_admission_merit_entries',
            'college_admission_seat_allocations',
            'admissions',
            'students',
        ];
        foreach ($blockingTables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'college_admission_application_id') && DB::table($table)->where('college_admission_application_id', $application->id)->exists()) {
                throw ValidationException::withMessages(['application' => 'This application already has downstream Admission/Student processing and cannot be withdrawn from this stage.']);
            }
        }

        DB::transaction(function () use ($application, $college, $actorId, $ip) {
            $before = $application->toArray();
            $application->update(['status' => 'WITHDRAWN', 'withdrawn_at' => now(), 'updated_by' => $actorId]);
            $this->audit('COLLEGE_ADMISSION_APPLICATION_WITHDRAWN', $application, $college, $actorId, $ip, $before, $application->fresh()->toArray());
        });
    }

    private function allocateApplicationNumber(College $college, CollegeAdmissionCycle $cycle): string
    {
        $sequenceTable = 'college_admission_application_sequences';

        if (! Schema::hasTable($sequenceTable)) {
            throw ValidationException::withMessages([
                'application' => 'Admission Application numbering is not initialized. Run the latest database migrations and try again.',
            ]);
        }

        $existingMax = (int) (DB::table('college_admission_applications')
            ->where('college_id', $college->id)
            ->where('college_admission_cycle_id', $cycle->id)
            ->selectRaw('COALESCE(MAX(CAST(RIGHT(application_no, 6) AS UNSIGNED)), 0) as max_sequence')
            ->value('max_sequence') ?? 0);

        DB::table($sequenceTable)->insertOrIgnore([
            'college_id' => $college->id,
            'college_admission_cycle_id' => $cycle->id,
            'next_number' => $existingMax + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $sequence = DB::table($sequenceTable)
            ->where('college_id', $college->id)
            ->where('college_admission_cycle_id', $cycle->id)
            ->lockForUpdate()
            ->first();

        if (! $sequence) {
            throw ValidationException::withMessages([
                'application' => 'Admission Application number could not be allocated. Please retry.',
            ]);
        }

        $number = max(1, (int) $sequence->next_number);
        DB::table($sequenceTable)->where('id', $sequence->id)->update([
            'next_number' => $number + 1,
            'updated_at' => now(),
        ]);

        return strtoupper($college->code.'-'.$cycle->code.'-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT));
    }

    private function resolveChoices(College $college, CollegeAdmissionCycle $cycle, array $choices, string $admissionMode = 'REGULAR'): array
    {
        $seen = [];
        $resolved = [];
        foreach (array_values($choices) as $index => $input) {
            $intakeId = (int) ($input['college_program_intake_id'] ?? 0);
            $bucketKey = trim((string) ($input['bucket_key'] ?? ''));
            $identity = $intakeId.'|'.$bucketKey;
            if (isset($seen[$identity])) {
                throw ValidationException::withMessages(['choices' => 'The same Admission Seat Bucket cannot be selected more than once in one application.']);
            }
            $seen[$identity] = true;

            $intake = CollegeProgramIntake::query()
                ->with('offering')
                ->whereKey($intakeId)
                ->where('status', 'ACTIVE')
                ->whereHas('offering', fn ($q) => $q
                    ->where('college_id', $college->id)
                    ->where('id', $cycle->college_program_offering_id)
                    ->where('status', 'ACTIVE'))
                ->first();

            if (! $intake || ! $intake->offering) {
                throw ValidationException::withMessages(['choices' => 'Every seat-bucket choice must belong to the exact ACTIVE Program Offering of the selected Admission Cycle and have an ACTIVE Intake.']);
            }

            $bucket = $this->reservationService->availableBuckets($intake)->firstWhere('bucket_key', $bucketKey);
            if (! $bucket) {
                throw ValidationException::withMessages(['choices' => 'One selected Admission Seat Bucket is no longer valid for its Intake.']);
            }

            $plan = CollegeProgramReservationPlan::query()
                ->where('college_program_intake_id', $intake->id)
                ->where('bucket_key', $bucketKey)
                ->first();
            if ($plan && $plan->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['choices' => 'Reservation is configured but INACTIVE for one selected seat bucket. Activate that Reservation Plan first.']);
            }

            $rule = CollegeAdmissionSelectionRule::query()
                ->where('college_program_intake_id', $intake->id)
                ->where('bucket_key', $bucketKey)
                ->where('status', 'ACTIVE')
                ->orderByDesc('version_no')
                ->first();
            if ($admissionMode === 'REGULAR' && ! $rule) {
                throw ValidationException::withMessages(['choices' => 'Regular Admission requires an ACTIVE Merit / Roster / Selection Rule for every selected seat bucket.']);
            }
            if ($rule && (int) ($rule->college_program_reservation_plan_id ?? 0) !== (int) ($plan?->id ?? 0)) {
                throw ValidationException::withMessages(['choices' => 'The active Selection Rule reservation context no longer matches the current seat bucket. Review the Selection Rule before accepting applications.']);
            }

            $resolved[] = [
                'preference_no' => $index + 1,
                'college_program_intake_id' => $intake->id,
                'college_program_reservation_plan_id' => $plan?->id,
                'college_admission_selection_rule_id' => $rule?->id,
                'bucket_type' => $bucket['bucket_type'],
                'bucket_key' => $bucket['bucket_key'],
                'basis_capacity' => (int) $bucket['basis_capacity'],
            ];
        }

        return $resolved;
    }

    /**
     * Link a REGULAR application to its downstream eligibility/selection context
     * without asking the applicant to choose a seat bucket and without checking
     * remaining seat capacity. The applicant-facing academic preference decides
     * the discipline/specialization scope; Intake + ACTIVE Selection Rule decide
     * the processing context used by Eligibility -> Score/Interview -> Merit.
     */
    private function resolveRegularProcessingContextFromAcademicPreference(
        CollegeAdmissionApplication $application,
        College $college,
        CollegeAdmissionCycle $cycle,
    ): array {
        $preference = DB::table('college_admission_application_academic_preferences')
            ->where('college_admission_application_id', $application->id)
            ->first(['discipline_id', 'specialization_id']);

        if (! $preference) {
            throw ValidationException::withMessages([
                'application' => 'Regular Admission cannot enter Eligibility because its academic preference is missing. Review the application setup and submit again.',
            ]);
        }

        $disciplineId = $preference->discipline_id ? (int) $preference->discipline_id : null;
        $specializationId = $preference->specialization_id ? (int) $preference->specialization_id : null;

        $intakes = CollegeProgramIntake::query()
            ->with(['offering', 'allocations'])
            ->where('college_program_offering_id', $cycle->college_program_offering_id)
            ->where('status', 'ACTIVE')
            ->get();

        $matches = collect();

        foreach ($intakes as $intake) {
            foreach ($this->reservationService->availableBuckets($intake) as $bucket) {
                if (! $this->bucketMatchesAcademicPreference($intake, $bucket, $disciplineId, $specializationId)) {
                    continue;
                }

                $plan = CollegeProgramReservationPlan::query()
                    ->where('college_program_intake_id', $intake->id)
                    ->where('bucket_key', $bucket['bucket_key'])
                    ->first();

                if ($plan && $plan->status !== 'ACTIVE') {
                    continue;
                }

                $rule = CollegeAdmissionSelectionRule::query()
                    ->where('college_program_intake_id', $intake->id)
                    ->where('bucket_key', $bucket['bucket_key'])
                    ->where('status', 'ACTIVE')
                    ->orderByDesc('version_no')
                    ->first();

                if (! $rule) {
                    continue;
                }

                if ((int) ($rule->college_program_reservation_plan_id ?? 0) !== (int) ($plan?->id ?? 0)) {
                    continue;
                }

                $matches->push([
                    'preference_no' => 1,
                    'college_program_intake_id' => $intake->id,
                    'college_program_reservation_plan_id' => $plan?->id,
                    'college_admission_selection_rule_id' => $rule->id,
                    'bucket_type' => $bucket['bucket_type'],
                    'bucket_key' => $bucket['bucket_key'],
                    'basis_capacity' => (int) $bucket['basis_capacity'],
                ]);
            }
        }

        if ($matches->isEmpty()) {
            throw ValidationException::withMessages([
                'application' => 'Regular Admission is not ready for Eligibility. Configure one ACTIVE Intake and ACTIVE Merit / Roster / Selection Rule matching this applicant\'s Program, Discipline and Specialization. Seat availability is not checked at application submission.',
            ]);
        }

        if ($matches->count() > 1) {
            throw ValidationException::withMessages([
                'application' => 'Regular Admission has more than one valid Eligibility processing context for this Program / Discipline / Specialization. Keep only one applicable ACTIVE Intake + Selection Rule path before accepting applications.',
            ]);
        }

        return [$matches->first()];
    }

    private function bucketMatchesAcademicPreference(
        CollegeProgramIntake $intake,
        array $bucket,
        ?int $disciplineId,
        ?int $specializationId,
    ): bool {
        if ($intake->allocation_mode === 'PROGRAM') {
            return $bucket['bucket_type'] === 'PROGRAM';
        }

        $disciplineAllocationId = $bucket['discipline_allocation_id'] ?? null;
        if (! $disciplineAllocationId || ! $disciplineId) {
            return false;
        }

        $disciplineAllocation = $intake->allocations->firstWhere('id', (int) $disciplineAllocationId);
        if (! $disciplineAllocation || (int) $disciplineAllocation->discipline_id !== $disciplineId) {
            return false;
        }

        if ($specializationId) {
            if ($bucket['bucket_type'] !== 'SPECIALIZATION') {
                return false;
            }
            $specializationAllocationId = $bucket['specialization_allocation_id'] ?? null;
            $specializationAllocation = $specializationAllocationId
                ? $intake->allocations->firstWhere('id', (int) $specializationAllocationId)
                : null;

            return $specializationAllocation
                && (int) $specializationAllocation->specialization_id === $specializationId;
        }

        return $bucket['bucket_type'] === 'DISCIPLINE_GENERAL';
    }

    private function replaceChoices(CollegeAdmissionApplication $application, array $contexts): void
    {
        $application->choices()->delete();
        foreach ($contexts as $context) {
            $application->choices()->create([
                ...$context,
                'eligibility_status' => 'PENDING',
                'eligibility_reason' => null,
                'eligibility_checked_at' => null,
                'eligibility_checked_by' => null,
            ]);
        }
    }

    private function activeCycle(College $college, int $cycleId): CollegeAdmissionCycle
    {
        $cycle = CollegeAdmissionCycle::query()
            ->whereKey($cycleId)
            ->where('college_id', $college->id)
            ->where('status', 'ACTIVE')
            ->whereNotNull('college_program_offering_id')
            ->whereHas('programOffering', fn ($q) => $q->where('college_id', $college->id)->where('status', 'ACTIVE'))
            ->first();
        if (! $cycle) {
            throw ValidationException::withMessages(['college_admission_cycle_id' => 'Select an ACTIVE Admission Cycle linked to an ACTIVE Program Offering for this College.']);
        }
        return $cycle;
    }

    private function candidateData(array $data): array
    {
        return [
            'external_reference' => filled($data['external_reference'] ?? null) ? trim((string) $data['external_reference']) : null,
            'candidate_name' => trim((string) $data['candidate_name']),
            'email' => filled($data['email'] ?? null) ? strtolower(trim((string) $data['email'])) : null,
            'phone' => filled($data['phone'] ?? null) ? trim((string) $data['phone']) : null,
            'date_of_birth' => $data['date_of_birth'],
            'remarks' => filled($data['remarks'] ?? null) ? trim((string) $data['remarks']) : null,
        ];
    }

    private function assertOwned(CollegeAdmissionApplication $application, College $college): void
    {
        abort_unless((int) $application->college_id === (int) $college->id, 404);
    }

    private function assertDraft(CollegeAdmissionApplication $application): void
    {
        if ($application->status !== 'DRAFT') {
            throw ValidationException::withMessages(['application' => 'Only a DRAFT application can be edited or submitted.']);
        }
    }

    private function assertCollegeActive(College $college): void
    {
        if ($college->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['college' => 'Admission Applications cannot be changed while this College is inactive.']);
        }
    }

    private function applicationRelations(): array
    {
        return [
            'admissionCycle.academicSession:id,name,code,is_current',
            'admissionCycle.programOffering.programTemplate:id,name,code',
            'admissionCycle.programOffering.academicSession:id,name,code,is_current',
            'choices.intake.offering.programTemplate:id,name,code',
            'choices.intake.offering.academicSession:id,name,code,is_current',
            'choices.reservationPlan:id,status',
            'choices.selectionRule:id,name,code,version_no,selection_mode,status,merit_weight_percent,entrance_weight_percent,interview_weight_percent',
            'formTemplate:id,name,code',
            'fieldValues.field:id,label,field_type',
            'academicPreference.discipline:id,name,code',
            'academicPreference.specialization:id,name,code',
            'courseChoices.course:id,name,code',
        ];
    }

    private function audit(string $event, CollegeAdmissionApplication $application, College $college, ?int $actorId, ?string $ip, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId,
            'event' => $event,
            'resource_type' => 'CollegeAdmissionApplication',
            'resource_id' => $application->id,
            'scope_type' => 'COLLEGE',
            'scope_reference' => 'college:'.$college->id,
            'before' => $before ? json_encode($before) : null,
            'after' => $after ? json_encode($after) : null,
            'ip_address' => $ip,
            'created_at' => now(),
        ]);
    }
}
