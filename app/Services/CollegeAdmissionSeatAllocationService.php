<?php

namespace App\Services;

use App\Models\College;
use App\Models\CollegeAdmissionDocumentVerification;
use App\Models\CollegeAdmissionApplicationChoice;
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
                'application:id,application_no,candidate_name,email,status,college_admission_cycle_id,college_admission_form_template_id',
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

        // Admission Confirmation consumes a seat allocation. Surface the linked
        // admission lifecycle state to the UI so a CONFIRMED admission cannot
        // expose an actionable Cancel Allocation control. The backend guard
        // below remains authoritative against stale/tampered requests.
        $admissionStatusByAllocation = collect();
        if (Schema::hasTable('admissions')
            && Schema::hasColumn('admissions', 'college_admission_seat_allocation_id')
            && Schema::hasColumn('admissions', 'status')) {
            $allocationIds = $allocationByMerit->pluck('id')->filter()->values();
            if ($allocationIds->isNotEmpty()) {
                $admissionStatusByAllocation = DB::table('admissions')
                    ->whereIn('college_admission_seat_allocation_id', $allocationIds)
                    ->pluck('status', 'college_admission_seat_allocation_id');
            }
        }

        $candidateCategoryOptions = DB::table('reservation_categories')->where('university_id',$college->university_id)->where('status','ACTIVE')->where('nature','VERTICAL')->whereRaw("LOWER(TRIM(code)) NOT IN ('general','gen','open','unreserved','ur')")->orderBy('display_order')->orderBy('name')->get(['id','name','code'])->map(fn($r)=>['id'=>(int)$r->id,'name'=>$r->name,'code'=>$r->code])->values()->all();

        return [
            'summary' => [
                'roster_count' => $meritRows->count(),
                'allocated_count' => $allocationByMerit->where('status', 'ALLOCATED')->count(),
                'cancelled_count' => $allocationByMerit->where('status', 'CANCELLED')->count(),
                'remaining_physical_seats' => max(0, $capacity['basis_capacity'] - $capacity['total_used']),
            ],
            'capacity' => $capacity,
            'candidate_category_options' => $candidateCategoryOptions,
            'rows' => $meritRows->map(function (CollegeAdmissionMeritEntry $merit) use ($allocationByMerit, $college, $admissionStatusByAllocation) {
                /** @var CollegeAdmissionSeatAllocation|null $allocation */
                $allocation = $allocationByMerit->get($merit->id);
                $preference = $merit->application?->academicPreference;
                $mappedCandidateCategory = $merit->application ? $this->mappedCandidateReservationCategory($college, $merit->application) : null;

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
                    'candidate_reservation_category' => $allocation?->candidate_category_source ? ['id'=>$allocation->candidate_reservation_category_id ? (int)$allocation->candidate_reservation_category_id : null,'code'=>$allocation->candidate_category_code,'name'=>$allocation->candidate_category_name,'source'=>$allocation->candidate_category_source] : $mappedCandidateCategory,
                    'allocation' => $allocation ? [
                        'id' => $allocation->id,
                        'status' => $allocation->status,
                        'physical_seat_type' => $allocation->physical_seat_type,
                        'candidate_reservation_category_id' => $allocation->candidate_reservation_category_id,
                        'physical_category_code' => $allocation->physical_category_code,
                        'physical_category_name' => $allocation->physical_category_name,
                        'allocation_round' => (int) $allocation->allocation_round,
                        'decision_note' => $allocation->decision_note,
                        'allocated_at' => optional($allocation->allocated_at)->toIso8601String(),
                        'cancellation_reason' => $allocation->cancellation_reason,
                        'admission_status' => $admissionStatusByAllocation->get($allocation->id),
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

    public function directScreen(College $college): array
    {
        $choices = CollegeAdmissionApplicationChoice::query()
            ->whereHas('application', fn ($q) => $q
                ->where('college_id', $college->id)
                ->where('status', 'SUBMITTED')
                ->where('admission_mode', 'DIRECT'))
            ->with([
                'application:id,college_id,application_no,candidate_name,status,admission_mode,college_admission_cycle_id,college_admission_form_template_id',
                'application.documentVerification:id,college_admission_application_id,status,finalized_at',
                'application.academicPreference.discipline:id,name,code',
                'application.academicPreference.specialization:id,name,code',
                'intake.offering.programTemplate:id,name,code',
                'intake.offering.academicSession:id,name,code,is_current',
                'reservationPlan.allocations.category',
                'seatAllocation.horizontalCategories',
            ])
            ->orderBy('id')
            ->get();

        $allocationIds = $choices->pluck('seatAllocation.id')->filter()->values();
        $admissionStatusByAllocation = collect();
        if ($allocationIds->isNotEmpty() && Schema::hasTable('admissions')) {
            $admissionStatusByAllocation = DB::table('admissions')
                ->whereIn('college_admission_seat_allocation_id', $allocationIds)
                ->pluck('status', 'college_admission_seat_allocation_id');
        }

        $candidateCategoryOptions = DB::table('reservation_categories')
            ->where('university_id', $college->university_id)
            ->where('status', 'ACTIVE')
            ->where('nature', 'VERTICAL')
            ->whereRaw("LOWER(TRIM(code)) NOT IN ('general','gen','open','unreserved','ur')")
            ->orderBy('display_order')->orderBy('name')
            ->get(['id','name','code'])
            ->map(fn ($r) => ['id'=>(int)$r->id,'name'=>$r->name,'code'=>$r->code])
            ->values()->all();

        $rows = $choices->map(function (CollegeAdmissionApplicationChoice $choice) use ($college, $admissionStatusByAllocation) {
            $application = $choice->application;
            $allocation = $choice->seatAllocation;
            $preference = $application?->academicPreference;
            $capacity = $this->capacitySnapshotForChoice($college, $choice);
            $mappedCandidateCategory = $application ? $this->mappedCandidateReservationCategory($college, $application) : null;
            $offering = $choice->intake?->offering;

            return [
                'choice_id' => $choice->id,
                'application_id' => $application?->id,
                'application_no' => $application?->application_no,
                'candidate_name' => $application?->candidate_name,
                'admission_mode' => 'DIRECT',
                'rank' => null,
                'final_weighted_score' => null,
                'preference_no' => (int) ($choice->preference_no ?? 1),
                'program_name' => $offering?->programTemplate?->name,
                'session_name' => $offering?->academicSession?->name,
                'bucket_key' => $choice->bucket_key,
                'document_verification' => $application?->documentVerification ? [
                    'id' => $application->documentVerification->id,
                    'status' => $application->documentVerification->status,
                    'finalized_at' => optional($application->documentVerification->finalized_at)->toIso8601String(),
                ] : null,
                'discipline_name' => $preference?->discipline?->name,
                'discipline_code' => $preference?->discipline?->code,
                'specialization_name' => $preference?->specialization?->name,
                'specialization_code' => $preference?->specialization?->code,
                'candidate_reservation_category' => $allocation?->candidate_category_source
                    ? ['id'=>$allocation->candidate_reservation_category_id ? (int)$allocation->candidate_reservation_category_id : null,'code'=>$allocation->candidate_category_code,'name'=>$allocation->candidate_category_name,'source'=>$allocation->candidate_category_source]
                    : $mappedCandidateCategory,
                'capacity' => $capacity,
                'allocation' => $allocation ? [
                    'id' => $allocation->id,
                    'status' => $allocation->status,
                    'physical_seat_type' => $allocation->physical_seat_type,
                    'candidate_reservation_category_id' => $allocation->candidate_reservation_category_id,
                    'physical_category_code' => $allocation->physical_category_code,
                    'physical_category_name' => $allocation->physical_category_name,
                    'allocation_round' => (int) $allocation->allocation_round,
                    'decision_note' => $allocation->decision_note,
                    'allocated_at' => optional($allocation->allocated_at)->toIso8601String(),
                    'cancellation_reason' => $allocation->cancellation_reason,
                    'admission_status' => $admissionStatusByAllocation->get($allocation->id),
                    'horizontal_categories' => $allocation->horizontalCategories->map(fn ($row) => [
                        'id' => $row->reservation_category_id,
                        'code' => $row->category_code,
                        'name' => $row->category_name,
                        'fulfills_target' => (bool) $row->fulfills_target,
                    ])->values()->all(),
                ] : null,
            ];
        })->values();

        return [
            'summary' => [
                'direct_candidates' => $rows->count(),
                'verified' => $rows->where('document_verification.status', 'VERIFIED')->count(),
                'allocated_count' => $rows->where('allocation.status', 'ALLOCATED')->count(),
            ],
            'candidate_category_options' => $candidateCategoryOptions,
            'rows' => $rows->all(),
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
            $mappedCandidateCategory = $this->mappedCandidateReservationCategory($college, $lockedMerit->application);
            $manualCandidateSelection = trim((string)($data['candidate_reservation_category_selection'] ?? ''));
            $candidateCategory = $mappedCandidateCategory;
            if (! $candidateCategory) {
                if ($manualCandidateSelection === '') {
                    throw ValidationException::withMessages(['candidate_reservation_category_selection'=>'Candidate Reservation Category is not mapped/resolved from the Admission Form. Confirm it manually before allocating the seat.']);
                }
                if (strtoupper($manualCandidateSelection) === 'GENERAL') {
                    $candidateCategory = ['id'=>null,'name'=>'General / Unreserved','code'=>'GENERAL','source'=>'MANUAL'];
                } else {
                    $row = DB::table('reservation_categories')->where('id',(int)$manualCandidateSelection)->where('university_id',$college->university_id)->where('status','ACTIVE')->where('nature','VERTICAL')->first(['id','name','code']);
                    if (! $row) throw ValidationException::withMessages(['candidate_reservation_category_selection'=>'Select General / Unreserved or an ACTIVE Reservation Category from this University.']);
                    $candidateCategory = ['id'=>(int)$row->id,'name'=>$row->name,'code'=>$row->code,'source'=>'MANUAL'];
                }
            }

            $seatContext = $this->lockedSeatContext($college, $rule);
            $physicalCategoryId = isset($data['physical_reservation_category_id']) && $data['physical_reservation_category_id'] !== ''
                ? (int) $data['physical_reservation_category_id']
                : null;

            // Candidate identity and physical seat bucket are intentionally separate,
            // but an operator may never use that separation to cross-allocate a
            // reserved seat. OPEN remains category-neutral; a reserved bucket is
            // available only to a candidate of that exact verified/mapped category.
            if ($physicalCategoryId !== null && (int) ($candidateCategory['id'] ?? 0) !== $physicalCategoryId) {
                throw ValidationException::withMessages([
                    'physical_reservation_category_id' => 'This reserved seat can be allocated only to a candidate of the same Reservation Category. Use Open / Unreserved when the candidate is entitled by merit.',
                ]);
            }

            // Manual counselling must not bypass roster order. OPEN is common
            // merit, therefore a lower-ranked candidate cannot consume an OPEN
            // seat while a higher-ranked VERIFIED + eligible candidate in this
            // exact roster is still waiting. Reserved seats apply the same rule
            // within the candidate's own reservation category.
            $this->assertMeritPriorityForSeat($college, $lockedMerit, $candidateCategory, $physicalCategoryId);

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
                'candidate_reservation_category_id' => $candidateCategory['id'],
                'candidate_category_source' => $candidateCategory['source'],
                'candidate_category_code' => $candidateCategory['code'],
                'candidate_category_name' => $candidateCategory['name'],
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

    public function allocateDirect(
        College $college,
        CollegeAdmissionApplicationChoice $choice,
        array $data,
        int $actorId,
        ?string $ip
    ): CollegeAdmissionSeatAllocation {
        $choice->loadMissing(['application', 'intake.offering', 'reservationPlan']);
        $application = $choice->application;
        abort_unless($application && (int) $application->college_id === (int) $college->id, 404);

        return DB::transaction(function () use ($college, $choice, $data, $actorId, $ip) {
            $lockedChoice = CollegeAdmissionApplicationChoice::query()
                ->whereKey($choice->id)
                ->lockForUpdate()
                ->with(['application', 'intake.offering', 'reservationPlan'])
                ->firstOrFail();
            $application = $lockedChoice->application;

            if (! $application || strtoupper((string) $application->admission_mode) !== 'DIRECT' || $application->status !== 'SUBMITTED') {
                throw ValidationException::withMessages(['allocation' => 'Only a SUBMITTED Direct Admission application can use the Direct Seat Allocation path.']);
            }
            if ($lockedChoice->college_admission_selection_rule_id !== null) {
                throw ValidationException::withMessages(['allocation' => 'This application choice is Selection Rule-driven and must use the Merit / Roster allocation path.']);
            }

            $documentVerification = CollegeAdmissionDocumentVerification::query()
                ->where('college_admission_application_id', $application->id)
                ->lockForUpdate()->first();
            if (! $documentVerification || $documentVerification->status !== 'VERIFIED') {
                throw ValidationException::withMessages(['allocation' => 'Document Verification must be finalized as VERIFIED before Direct Admission can consume a seat.']);
            }

            CollegeAdmissionSeatAllocation::query()
                ->where('college_admission_application_id', $application->id)
                ->lockForUpdate()->get();
            $existing = CollegeAdmissionSeatAllocation::query()
                ->where('college_admission_application_choice_id', $lockedChoice->id)
                ->lockForUpdate()->first();
            $otherActive = CollegeAdmissionSeatAllocation::query()
                ->where('college_admission_application_id', $application->id)
                ->where('status', 'ALLOCATED')
                ->when($existing, fn ($q) => $q->where('id', '<>', $existing->id))
                ->exists();
            if ($otherActive) {
                throw ValidationException::withMessages(['allocation' => 'This Direct Admission application already consumes a physical seat. Cancel that allocation first.']);
            }
            if ($existing && $existing->status === 'ALLOCATED') {
                throw ValidationException::withMessages(['allocation' => 'This Direct Admission application already has an active seat allocation.']);
            }

            $mappedCandidateCategory = $this->mappedCandidateReservationCategory($college, $application);
            $manualCandidateSelection = trim((string) ($data['candidate_reservation_category_selection'] ?? ''));
            $candidateCategory = $mappedCandidateCategory;
            if (! $candidateCategory) {
                if ($manualCandidateSelection === '') {
                    throw ValidationException::withMessages(['candidate_reservation_category_selection' => 'Candidate Reservation Category was not captured by the system form field. Confirm it manually before allocating the seat.']);
                }
                if (strtoupper($manualCandidateSelection) === 'GENERAL') {
                    $candidateCategory = ['id'=>null,'name'=>'General / Unreserved','code'=>'GENERAL','source'=>'MANUAL'];
                } else {
                    $row = DB::table('reservation_categories')->where('id',(int)$manualCandidateSelection)->where('university_id',$college->university_id)->where('status','ACTIVE')->where('nature','VERTICAL')->first(['id','name','code']);
                    if (! $row) throw ValidationException::withMessages(['candidate_reservation_category_selection'=>'Select General / Unreserved or an ACTIVE Reservation Category from this University.']);
                    $candidateCategory = ['id'=>(int)$row->id,'name'=>$row->name,'code'=>$row->code,'source'=>'MANUAL'];
                }
            }

            $context = $this->lockedSeatContextForChoice($college, $lockedChoice);
            $physicalCategoryId = isset($data['physical_reservation_category_id']) && $data['physical_reservation_category_id'] !== ''
                ? (int) $data['physical_reservation_category_id'] : null;
            if ($physicalCategoryId !== null && (int) ($candidateCategory['id'] ?? 0) !== $physicalCategoryId) {
                throw ValidationException::withMessages(['physical_reservation_category_id' => 'This reserved seat can be allocated only to a candidate of the same Reservation Category. Use Open / Unreserved when applicable.']);
            }

            $physical = $this->validatePhysicalSeat($context, $physicalCategoryId, $existing?->id);
            $horizontalIds = collect($data['horizontal_category_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            $targetIds = collect($data['horizontal_target_category_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
            $horizontalRows = $this->validateHorizontalSelections($context, $horizontalIds, $targetIds, $existing?->id);

            $values = [
                'college_id' => $college->id,
                'college_program_intake_id' => $lockedChoice->college_program_intake_id,
                'bucket_type' => $lockedChoice->bucket_type,
                'bucket_key' => $lockedChoice->bucket_key,
                'college_program_reservation_plan_id' => $lockedChoice->college_program_reservation_plan_id,
                'college_admission_merit_entry_id' => null,
                'college_admission_application_id' => $application->id,
                'college_admission_application_choice_id' => $lockedChoice->id,
                'college_admission_document_verification_id' => $documentVerification->id,
                'college_admission_score_id' => null,
                'college_admission_selection_rule_id' => null,
                'merit_rank' => null,
                'final_weighted_score' => null,
                'candidate_reservation_category_id' => $candidateCategory['id'],
                'candidate_category_source' => $candidateCategory['source'],
                'candidate_category_code' => $candidateCategory['code'],
                'candidate_category_name' => $candidateCategory['name'],
                'physical_seat_type' => $physical['seat_type'],
                'physical_reservation_category_id' => $physical['category_id'],
                'physical_category_code' => $physical['category_code'],
                'physical_category_name' => $physical['category_name'],
                'allocation_round' => max(1, (int) ($data['allocation_round'] ?? 1)),
                'status' => 'ALLOCATED',
                'decision_note' => filled($data['decision_note'] ?? null) ? trim((string) $data['decision_note']) : null,
                'allocated_at' => now(),
                'allocated_by' => $actorId,
                'cancelled_at' => null,
                'cancelled_by' => null,
                'cancellation_reason' => null,
            ];
            $before = $existing?->toArray();
            if ($existing) {
                $existing->update($values);
                $allocation = $existing->fresh();
                $allocation->horizontalCategories()->delete();
            } else {
                $allocation = CollegeAdmissionSeatAllocation::create($values);
            }
            foreach ($horizontalRows as $row) {
                $allocation->horizontalCategories()->create([
                    'reservation_category_id' => $row['category_id'],
                    'category_code' => $row['category_code'],
                    'category_name' => $row['category_name'],
                    'fulfills_target' => $row['fulfills_target'],
                    'created_by' => $actorId,
                ]);
            }
            $fresh = $allocation->fresh()->load('horizontalCategories');
            $this->audit('COLLEGE_DIRECT_ADMISSION_SEAT_ALLOCATED', $fresh, $college, $actorId, $ip, $before, $fresh->toArray());
            return $fresh;
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
                && Schema::hasColumn('admissions', 'status')
                && DB::table('admissions')
                    ->where('college_admission_seat_allocation_id', $locked->id)
                    ->where('status', 'CONFIRMED')
                    ->exists()) {
                throw ValidationException::withMessages([
                    'allocation' => 'Cannot cancel this seat allocation because the candidate already has a CONFIRMED admission. Revoke the admission first, then cancel the seat allocation.',
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

    private function lockedSeatContextForChoice(College $college, CollegeAdmissionApplicationChoice $choice): array
    {
        $choice->loadMissing(['intake.offering', 'reservationPlan.allocations.category']);
        abort_unless((int) $choice->intake?->offering?->college_id === (int) $college->id, 404);

        DB::table('college_program_intakes')->where('id', $choice->college_program_intake_id)->lockForUpdate()->get();
        $plan = null;
        $vertical = collect();
        $horizontal = collect();
        if ($choice->college_program_reservation_plan_id) {
            $plan = CollegeProgramReservationPlan::query()
                ->whereKey($choice->college_program_reservation_plan_id)
                ->lockForUpdate()
                ->with(['intake.offering', 'allocations.category'])
                ->firstOrFail();
            if ((int) $plan->college_program_intake_id !== (int) $choice->college_program_intake_id || $plan->bucket_key !== $choice->bucket_key) {
                throw ValidationException::withMessages(['allocation' => 'The Reservation Plan no longer matches this Direct Admission seat bucket.']);
            }
            if ($plan->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['allocation' => 'The Reservation Plan for this Direct Admission seat bucket must remain ACTIVE until Seat Allocation is complete.']);
            }
            CollegeProgramReservationAllocation::query()->where('college_program_reservation_plan_id', $plan->id)->lockForUpdate()->get();
            $active = $plan->allocations->where('status', 'ACTIVE')->filter(fn ($row) => $row->category);
            $vertical = $active->filter(fn ($row) => $row->category->nature === 'VERTICAL')->values();
            $horizontal = $active->filter(fn ($row) => $row->category->nature === 'HORIZONTAL')->values();
        }
        $basis = (int) $choice->basis_capacity;
        return [
            'rule' => null,
            'intake_id' => (int) $choice->college_program_intake_id,
            'bucket_key' => $choice->bucket_key,
            'plan' => $plan,
            'basis_capacity' => $basis,
            'open_capacity' => max(0, $basis - (int) $vertical->sum('seat_capacity')),
            'vertical' => $vertical,
            'horizontal' => $horizontal,
        ];
    }

    private function capacitySnapshotForChoice(College $college, CollegeAdmissionApplicationChoice $choice): array
    {
        $choice->loadMissing(['intake.offering', 'reservationPlan.allocations.category']);
        abort_unless((int) $choice->intake?->offering?->college_id === (int) $college->id, 404);
        $plan = $choice->reservationPlan;
        $vertical = collect();
        $horizontal = collect();
        if ($plan) {
            $active = $plan->allocations->where('status', 'ACTIVE')->filter(fn ($row) => $row->category);
            $vertical = $active->filter(fn ($row) => $row->category->nature === 'VERTICAL')->values();
            $horizontal = $active->filter(fn ($row) => $row->category->nature === 'HORIZONTAL')->values();
        }
        $basis = (int) $choice->basis_capacity;
        $openCapacity = max(0, $basis - (int) $vertical->sum('seat_capacity'));
        $allocations = CollegeAdmissionSeatAllocation::query()
            ->where('college_program_intake_id', $choice->college_program_intake_id)
            ->where('bucket_key', $choice->bucket_key)
            ->where('status', 'ALLOCATED')->get();
        $openUsed = $allocations->whereNull('physical_reservation_category_id')->count();
        $verticalRows = $vertical->map(function ($quota) use ($allocations) {
            $used = $allocations->where('physical_reservation_category_id', $quota->reservation_category_id)->count();
            return ['category_id'=>$quota->category->id,'code'=>$quota->category->code,'name'=>$quota->category->name,'capacity'=>(int)$quota->seat_capacity,'used'=>$used,'remaining'=>max(0,(int)$quota->seat_capacity-$used)];
        })->values();
        $horizontalRows = $horizontal->map(function ($quota) use ($choice) {
            $actual = CollegeAdmissionSeatAllocationHorizontalCategory::query()
                ->where('reservation_category_id', $quota->reservation_category_id)
                ->whereHas('allocation', fn ($q) => $q->where('college_program_intake_id',$choice->college_program_intake_id)->where('bucket_key',$choice->bucket_key)->where('status','ALLOCATED'))->count();
            $fulfilled = CollegeAdmissionSeatAllocationHorizontalCategory::query()
                ->where('reservation_category_id', $quota->reservation_category_id)->where('fulfills_target', true)
                ->whereHas('allocation', fn ($q) => $q->where('college_program_intake_id',$choice->college_program_intake_id)->where('bucket_key',$choice->bucket_key)->where('status','ALLOCATED'))->count();
            return ['category_id'=>$quota->category->id,'code'=>$quota->category->code,'name'=>$quota->category->name,'target'=>(int)$quota->seat_capacity,'fulfilled'=>$fulfilled,'remaining_target'=>max(0,(int)$quota->seat_capacity-$fulfilled),'actual_candidates'=>$actual];
        })->values();
        return [
            'basis_capacity'=>$basis,
            'total_used'=>$allocations->count(),
            'open'=>['capacity'=>$openCapacity,'used'=>$openUsed,'remaining'=>max(0,$openCapacity-$openUsed)],
            'vertical'=>$verticalRows->all(),
            'horizontal'=>$horizontalRows->all(),
            'has_reservation_plan'=>$plan !== null,
            'reservation_plan_id'=>$plan?->id,
            'reservation_plan_status'=>$plan?->status,
        ];
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
            'intake_id' => (int) $rule->college_program_intake_id,
            'bucket_key' => $rule->bucket_key,
            'plan' => $plan,
            'basis_capacity' => $basis,
            'open_capacity' => max(0, $basis - $verticalCapacity),
            'vertical' => $vertical,
            'horizontal' => $horizontal,
        ];
    }

    private function assertMeritPriorityForSeat(
        College $college,
        CollegeAdmissionMeritEntry $currentMerit,
        array $candidateCategory,
        ?int $physicalCategoryId
    ): void {
        $higherRows = CollegeAdmissionMeritEntry::query()
            ->where('college_id', $college->id)
            ->where('college_admission_selection_rule_id', $currentMerit->college_admission_selection_rule_id)
            ->where('rank', '<', $currentMerit->rank)
            ->with([
                'application:id,application_no,candidate_name,status,college_admission_cycle_id,college_admission_form_template_id',
                'application.documentVerification:id,college_admission_application_id,status',
                'choice:id,college_admission_application_id,eligibility_status',
            ])
            ->orderBy('rank')
            ->get();

        foreach ($higherRows as $higher) {
            $application = $higher->application;
            if (! $application || $application->status !== 'SUBMITTED'
                || $higher->choice?->eligibility_status !== 'ELIGIBLE'
                || $application->documentVerification?->status !== 'VERIFIED') {
                continue;
            }

            $alreadyAllocated = CollegeAdmissionSeatAllocation::query()
                ->where('college_admission_application_id', $application->id)
                ->where('status', 'ALLOCATED')
                ->exists();
            if ($alreadyAllocated) {
                continue;
            }

            if ($physicalCategoryId === null) {
                throw ValidationException::withMessages([
                    'physical_reservation_category_id' => 'OPEN merit order cannot be bypassed. Higher-ranked eligible candidate #'.$higher->rank.' '.$application->candidate_name.' ('.$application->application_no.') is still awaiting a seat decision.',
                ]);
            }

            $higherCategory = $this->mappedCandidateReservationCategory($college, $application);
            if ($higherCategory && (int) ($higherCategory['id'] ?? 0) === (int) ($candidateCategory['id'] ?? 0)) {
                throw ValidationException::withMessages([
                    'physical_reservation_category_id' => ($candidateCategory['name'] ?? 'Reserved').' merit order cannot be bypassed. Higher-ranked eligible candidate #'.$higher->rank.' '.$application->candidate_name.' ('.$application->application_no.') of the same category is still awaiting a seat decision.',
                ]);
            }
        }
    }

    private function validatePhysicalSeat(array $context, ?int $categoryId, ?int $ignoreAllocationId): array
    {
        $baseQuery = CollegeAdmissionSeatAllocation::query()
            ->where('college_program_intake_id', $context['intake_id'])
            ->where('bucket_key', $context['bucket_key'])
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
                        $query->where('college_program_intake_id', $context['intake_id'])
                            ->where('bucket_key', $context['bucket_key'])
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
            'intake_id' => (int) $rule->college_program_intake_id,
            'bucket_key' => $rule->bucket_key,
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

    private function mappedCandidateReservationCategory(College $college, $application): ?array
    {
        if (! $application?->id) return null;

        /*
         * ADR 136:
         * The application answer itself is authoritative when it belongs to a
         * system-mapped Candidate Reservation Category field. Resolve that
         * before the older cycle-level mapping so a category selected by the
         * applicant is automatically available during Seat Allocation.
         */
        $systemValue = DB::table('college_admission_application_field_values as fv')
            ->join('college_admission_form_fields as f', 'f.id', '=', 'fv.college_admission_form_field_id')
            ->where('fv.college_admission_application_id', $application->id)
            ->where('f.status', 'ACTIVE')
            ->where('f.system_purpose', 'CANDIDATE_RESERVATION_CATEGORY')
            ->orderByDesc('fv.id')
            ->value('fv.value_text');

        $resolved = $this->resolveCandidateCategoryValue($college, $systemValue, 'SYSTEM_FORM_FIELD');
        if ($resolved) return $resolved;

        // Backward-compatible fallback for forms configured through the
        // College Admission Form Mapping screen before system-purpose fields.
        if (! $application->college_admission_cycle_id) return null;
        $mapping = DB::table('college_admission_form_mappings')
            ->where('college_id',$college->id)
            ->where('college_admission_cycle_id',$application->college_admission_cycle_id)
            ->where('status','ACTIVE')
            ->whereNotNull('reservation_category_field_id')
            ->orderByDesc('id')
            ->first(['reservation_category_field_id']);

        if (! $mapping) return null;

        $mappedValue = DB::table('college_admission_application_field_values')
            ->where('college_admission_application_id',$application->id)
            ->where('college_admission_form_field_id',$mapping->reservation_category_field_id)
            ->value('value_text');

        return $this->resolveCandidateCategoryValue($college, $mappedValue, 'LEGACY_FORM_MAPPING');
    }

    private function resolveCandidateCategoryValue(College $college, mixed $value, string $source = 'SYSTEM_FORM_FIELD'): ?array
    {
        $value = trim((string) $value);
        if ($value === '') return null;

        $normalized = mb_strtolower($value);
        if (in_array($normalized, ['general','gen','general / unreserved','open','open / unreserved','unreserved','ur'], true)) {
            return ['id'=>null,'name'=>'General / Unreserved','code'=>'GENERAL','source'=>$source];
        }

        $category = DB::table('reservation_categories')
            ->where('university_id',$college->university_id)
            ->where('status','ACTIVE')
            ->where('nature','VERTICAL')
            ->where(function($q) use($normalized){
                $q->whereRaw('LOWER(TRIM(code)) = ?',[$normalized])
                    ->orWhereRaw('LOWER(TRIM(name)) = ?',[$normalized]);
            })
            ->first(['id','name','code']);

        return $category
            ? ['id'=>(int)$category->id,'name'=>$category->name,'code'=>$category->code,'source'=>$source]
            : null;
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
