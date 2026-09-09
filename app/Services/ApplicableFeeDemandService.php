<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\College;
use App\Models\CollegeFeeStructureAdoption;
use App\Models\CollegeProgramOffering;
use App\Models\FeeDemand;
use App\Models\FeeDemandItem;
use App\Models\FeeStructure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ApplicableFeeDemandService
{
    public function generateInitialForAdmission(College $college, Admission $admission, int $userId): ?FeeDemand
    {
        $existing = FeeDemand::query()
            ->where('admission_id', $admission->id)
            ->where('status', '!=', 'CANCELLED')
            ->where(function ($q) {
                $q->where('demand_context', 'ADMISSION_INITIAL')->orWhereNull('demand_context');
            })
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        $admission->loadMissing(['intake.offering']);
        $offering = $admission->intake?->offering;
        if (! $offering) {
            return null;
        }

        $snapshots = $this->initialSnapshots($college, $admission, $offering);
        if (! $snapshots) {
            return null;
        }

        return $this->persistDemand(
            $college,
            $admission,
            $offering,
            1,
            $snapshots,
            $userId,
            'ADMISSION_AUTO',
            'ADMISSION_INITIAL',
            'MIXED',
            'Admission Initial',
            null
        );
    }

    public function recoverInitial(College $college, Admission $admission, int $userId): FeeDemand
    {
        if ($admission->college_id !== $college->id || $admission->status !== 'CONFIRMED') {
            throw ValidationException::withMessages([
                'admission' => 'Only a CONFIRMED admission of this College can receive an initial fee demand.',
            ]);
        }

        $existing = FeeDemand::query()
            ->where('admission_id', $admission->id)
            ->where('status', '!=', 'CANCELLED')
            ->where(function ($q) {
                $q->where('demand_context', 'ADMISSION_INITIAL')->orWhereNull('demand_context');
            })
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'admission_id' => 'An active initial fee demand already exists for this admission: '.$existing->demand_no,
            ]);
        }

        $admission->loadMissing(['intake.offering']);
        $offering = $admission->intake?->offering;
        if (! $offering) {
            throw ValidationException::withMessages(['admission_id' => 'Admission has no Program Offering context.']);
        }

        $snapshots = $this->initialSnapshots($college, $admission, $offering);
        if (! $snapshots) {
            throw ValidationException::withMessages([
                'fee' => 'No Admission-purpose charges or first-period Academic charges requiring Enrollment Clearance are currently applicable to this admission.',
            ]);
        }

        return $this->persistDemand(
            $college,
            $admission,
            $offering,
            1,
            $snapshots,
            $userId,
            'MANUAL_RECOVERY',
            'ADMISSION_INITIAL',
            'MIXED',
            'Admission Initial',
            null
        );
    }

    /**
     * Installment execution contexts are derived from EXISTING active Fee Demands,
     * not from demand-generation contexts. This intentionally includes
     * ADMISSION_INITIAL / MIXED demands created automatically at Admission Confirmation.
     */
    public function installmentContexts(College $college, CollegeProgramOffering $offering): array
    {
        $purposeExpr = "CASE
            WHEN fee_demands.demand_context IS NOT NULL AND TRIM(fee_demands.demand_context) <> '' THEN UPPER(fee_demands.demand_context)
            WHEN UPPER(COALESCE(fee_demands.generation_mode,'')) IN ('ADMISSION_AUTO','MANUAL_RECOVERY') THEN 'ADMISSION_INITIAL'
            WHEN UPPER(COALESCE(item.purpose,'')) IN ('ADMISSION','ADMISSION_INITIAL') THEN 'ADMISSION_INITIAL'
            WHEN UPPER(COALESCE(item.purpose,'')) = 'ACADEMIC' THEN 'ACADEMIC'
            WHEN UPPER(COALESCE(item.purpose,'')) = 'EXAMINATION' THEN 'EXAMINATION'
            ELSE 'OTHER'
        END";

        $basisExpr = "CASE
            WHEN (".$purposeExpr.") = 'ADMISSION_INITIAL' THEN 'MIXED'
            WHEN fee_demands.billing_basis_group IS NOT NULL AND TRIM(fee_demands.billing_basis_group) <> '' THEN UPPER(fee_demands.billing_basis_group)
            WHEN UPPER(COALESCE(item.charge_basis,'')) IN ('PER_TERM','SPECIFIC_TERM') THEN 'TERM'
            WHEN UPPER(COALESCE(item.charge_basis,'')) IN ('PER_ACADEMIC_YEAR','SPECIFIC_ACADEMIC_YEAR') THEN 'ACADEMIC_YEAR'
            ELSE 'ONE_TIME'
        END";

        $periodExpr = 'COALESCE(fee_demands.billing_period_no, item.source_period_no, 1)';

        /*
         * ADR 153 / MySQL ONLY_FULL_GROUP_BY compatibility:
         * Normalize each Fee Demand Item in an inner query first, then aggregate
         * only by the normalized aliases in the outer query. Grouping directly
         * by repeated CASE expressions caused MySQL 8 / ONLY_FULL_GROUP_BY to
         * treat raw demand_context/billing_basis_group references as non-grouped.
         */
        $normalized = FeeDemand::query()
            ->join('fee_demand_items as item', 'item.fee_demand_id', '=', 'fee_demands.id')
            ->where('fee_demands.college_id', $college->id)
            ->where('fee_demands.college_program_offering_id', $offering->id)
            ->where('fee_demands.status', '!=', 'CANCELLED')
            ->where('item.installment_allowed', true)
            ->selectRaw('fee_demands.admission_id as admission_id')
            ->selectRaw('item.id as item_id')
            ->selectRaw($purposeExpr.' as normalized_purpose')
            ->selectRaw($basisExpr.' as normalized_basis_group')
            ->selectRaw($periodExpr.' as normalized_period_no')
            ->selectRaw("COALESCE(fee_demands.billing_period_label,'') as raw_period_label");

        return DB::query()
            ->fromSub($normalized, 'installment_scope')
            ->select([
                'normalized_purpose',
                'normalized_basis_group',
                'normalized_period_no',
            ])
            ->selectRaw("MAX(raw_period_label) as raw_period_label")
            ->selectRaw('COUNT(DISTINCT admission_id) as cohort_count')
            ->selectRaw('COUNT(item_id) as item_count')
            ->groupBy('normalized_purpose', 'normalized_basis_group', 'normalized_period_no')
            ->orderBy('normalized_period_no')
            ->get()
            ->map(function ($row) {
                $purpose = strtoupper((string) $row->normalized_purpose);
                $basis = strtoupper((string) $row->normalized_basis_group);
                $periodNo = (int) $row->normalized_period_no;
                $rawLabel = trim((string) ($row->raw_period_label ?? ''));

                $label = $purpose === 'ADMISSION_INITIAL'
                    ? 'Admission Initial'
                    : ($rawLabel !== '' ? $rawLabel : str_replace('_', ' ', $purpose).' · Period '.$periodNo);

                return [
                    'key' => $purpose.'|'.$basis.'|'.$periodNo,
                    'purpose' => $purpose,
                    'basis_group' => $basis,
                    'period_no' => $periodNo,
                    'label' => $label,
                    'ready' => true,
                    'reason' => null,
                    'cohort_count' => (int) $row->cohort_count,
                    'item_count' => (int) $row->item_count,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Bulk contexts are derived from the effective Fee Setup itself.
     * No independent Semester/Year list is maintained by Fee Demand.
     */
    public function bulkContexts(College $college, CollegeProgramOffering $offering, ?object $academicPolicy = null): array
    {
        $offering->loadMissing(['programTemplate', 'curriculum.terms']);
        $structures = $this->effectiveStructures(
            $college,
            $offering->id,
            $offering->academic_session_id,
            $offering->program_template_id,
            $offering->curriculum_id
        );

        $contexts = [];
        foreach ($structures as $structure) {
            if ($structure->purpose === 'ADMISSION') {
                continue; // Admission demand is automatic/recovery, not a routine bulk action.
            }

            foreach ($this->periodsForStructure($structure, $offering) as $periodNo) {
                if (! count($this->effectiveItems($structure, $periodNo))) {
                    continue;
                }

                $group = $this->basisGroup($structure->charge_basis);
                $key = $structure->purpose.'|'.$group.'|'.$periodNo;
                $contexts[$key] ??= [
                    'key' => $key,
                    'purpose' => $structure->purpose,
                    'basis_group' => $group,
                    'period_no' => $periodNo,
                    'label' => $this->periodLabel($offering, $group, $periodNo),
                    'ready' => false,
                    'reason' => null,
                ];
            }
        }

        $confirmedCount = Admission::query()
            ->where('college_id', $college->id)
            ->where('status', 'CONFIRMED')
            ->whereHas('intake', fn ($q) => $q->where('college_program_offering_id', $offering->id))
            ->count();

        foreach ($contexts as &$context) {
            $isFirstAcademicContext = $context['purpose'] === 'ACADEMIC' && (int) $context['period_no'] === 1;

            if ($isFirstAcademicContext) {
                $context['ready'] = $confirmedCount > 0;
                $context['reason'] = $confirmedCount > 0
                    ? 'First academic-period bulk demand can use the CONFIRMED admission cohort because no previous academic progression exists yet.'
                    : 'No CONFIRMED admissions are available in this Program Offering.';
            } elseif ($context['purpose'] === 'ACADEMIC') {
                $context['ready'] = false;
                $context['reason'] = $academicPolicy
                    ? 'The University Academic Policy is resolved, but authoritative Student Enrollment + Academic Progression results are not implemented yet. Fee Management will not calculate promotion eligibility itself.'
                    : 'No current ACTIVE + APPROVED University Academic Policy resolves for this Program Offering. Later-period Academic demand remains blocked.';
            } elseif ($context['purpose'] === 'EXAMINATION') {
                $context['ready'] = false;
                $context['reason'] = 'Examination demand requires authoritative examination eligibility/registration data. It is intentionally blocked until that workflow exists.';
            } else {
                $context['ready'] = false;
                $context['reason'] = 'OTHER-purpose bulk demand requires an explicit target-cohort rule. Fee Management will not charge the whole Program Offering without that rule.';
            }

            $context['cohort_count'] = $confirmedCount;
        }
        unset($context);

        return array_values($contexts);
    }

    public function generateIndividual(
        College $college,
        CollegeProgramOffering $offering,
        Admission $admission,
        string $purpose,
        string $basisGroup,
        int $periodNo,
        int $userId,
        ?object $academicPolicy = null
    ): FeeDemand {
        if ($offering->college_id !== $college->id || $offering->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['offering_id' => 'Select an ACTIVE Program Offering of this College.']);
        }
        if ($admission->college_id !== $college->id || $admission->status !== 'CONFIRMED') {
            throw ValidationException::withMessages(['admission_id' => 'Select a CONFIRMED admission of this College.']);
        }

        $admission->loadMissing(['intake.offering']);
        if ((int) $admission->intake?->college_program_offering_id !== (int) $offering->id) {
            throw ValidationException::withMessages([
                'admission_id' => 'The selected admission does not belong to this Program Offering.',
            ]);
        }

        $matching = collect($this->bulkContexts($college, $offering, $academicPolicy))
            ->first(fn ($context) => $context['purpose'] === $purpose
                && $context['basis_group'] === $basisGroup
                && (int) $context['period_no'] === $periodNo);

        if (! $matching) {
            throw ValidationException::withMessages([
                'billing_context' => 'The selected demand context is not available from the current ACTIVE Fee Setup.',
            ]);
        }
        if (! $matching['ready']) {
            throw ValidationException::withMessages(['billing_context' => $matching['reason']]);
        }

        $snapshots = $this->contextSnapshots($college, $admission, $offering, $purpose, $basisGroup, $periodNo);
        if (! $snapshots) {
            throw ValidationException::withMessages([
                'admission_id' => 'No new applicable fee item remains for this candidate/student in the selected billing context. Existing active demand items are not duplicated.',
            ]);
        }

        return $this->persistDemand(
            $college,
            $admission,
            $offering,
            $periodNo,
            $snapshots,
            $userId,
            'INDIVIDUAL_PERIOD',
            $purpose,
            $basisGroup,
            $matching['label'],
            null
        );
    }

    public function generateBulk(
        College $college,
        CollegeProgramOffering $offering,
        string $purpose,
        string $basisGroup,
        int $periodNo,
        int $userId,
        ?object $academicPolicy = null
    ): array {
        if ($offering->college_id !== $college->id || $offering->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['offering_id' => 'Select an ACTIVE Program Offering of this College.']);
        }

        $matching = collect($this->bulkContexts($college, $offering, $academicPolicy))
            ->first(fn ($context) => $context['purpose'] === $purpose
                && $context['basis_group'] === $basisGroup
                && (int) $context['period_no'] === $periodNo);

        if (! $matching) {
            throw ValidationException::withMessages([
                'billing_context' => 'The selected demand context is not available from the current ACTIVE Fee Setup.',
            ]);
        }
        if (! $matching['ready']) {
            throw ValidationException::withMessages(['billing_context' => $matching['reason']]);
        }
        if ($purpose !== 'ACADEMIC' || $periodNo !== 1) {
            throw ValidationException::withMessages([
                'billing_context' => 'Only the first Academic billing period is executable before authoritative Student Enrollment/Academic Progression is available.',
            ]);
        }

        $admissions = Admission::query()
            ->with(['intake.offering'])
            ->where('college_id', $college->id)
            ->where('status', 'CONFIRMED')
            ->whereHas('intake', fn ($q) => $q->where('college_program_offering_id', $offering->id))
            ->orderBy('id')
            ->get();

        $runKey = 'FDB-'.now()->format('YmdHis').'-'.strtoupper(substr((string) Str::uuid(), 0, 8));
        $created = 0;
        $skipped = 0;
        $errors = [];

        foreach ($admissions as $admission) {
            try {
                $snapshots = $this->contextSnapshots($college, $admission, $offering, $purpose, $basisGroup, $periodNo);
                if (! $snapshots) {
                    $skipped++;
                    continue;
                }

                $this->persistDemand(
                    $college,
                    $admission,
                    $offering,
                    $periodNo,
                    $snapshots,
                    $userId,
                    'BULK_PERIOD',
                    $purpose,
                    $basisGroup,
                    $matching['label'],
                    $runKey
                );
                $created++;
            } catch (ValidationException $e) {
                $errors[] = $admission->admission_no.': '.collect($e->errors())->flatten()->first();
            }
        }

        return [
            'run_key' => $runKey,
            'created' => $created,
            'skipped' => $skipped,
            'errors' => $errors,
            'cohort_count' => $admissions->count(),
            'label' => $matching['label'],
        ];
    }

    private function initialSnapshots(College $college, Admission $admission, CollegeProgramOffering $offering): array
    {
        $structures = $this->effectiveStructures(
            $college,
            $offering->id,
            $offering->academic_session_id,
            $offering->program_template_id,
            $admission->curriculum_id ?: $offering->curriculum_id
        );

        $rows = [];
        foreach ($structures as $structure) {
            foreach ($this->effectiveItems($structure, 1) as $row) {
                if ($structure->purpose === 'ADMISSION') {
                    $rows[] = $row;
                    continue;
                }

                // Admission Fee is optional. If the institution uses a first Academic charge
                // as the enrollment gate, only the exact period item explicitly marked for
                // Enrollment Clearance is included in the admission-stage demand.
                if ($structure->purpose === 'ACADEMIC' && $row['is_enrollment_clearance_required']) {
                    $rows[] = $row;
                }
            }
        }

        return $this->deduplicateAndRemoveAlreadyDemanded($admission, $rows);
    }

    private function contextSnapshots(
        College $college,
        Admission $admission,
        CollegeProgramOffering $offering,
        string $purpose,
        string $basisGroup,
        int $periodNo
    ): array {
        $structures = $this->effectiveStructures(
            $college,
            $offering->id,
            $offering->academic_session_id,
            $offering->program_template_id,
            $admission->curriculum_id ?: $offering->curriculum_id
        );

        $rows = [];
        foreach ($structures as $structure) {
            if ($structure->purpose !== $purpose || $this->basisGroup($structure->charge_basis) !== $basisGroup) {
                continue;
            }
            foreach ($this->effectiveItems($structure, $periodNo) as $row) {
                $rows[] = $row;
            }
        }

        return $this->deduplicateAndRemoveAlreadyDemanded($admission, $rows);
    }

    private function deduplicateAndRemoveAlreadyDemanded(Admission $admission, array $rows): array
    {
        $seenHeads = [];
        $result = [];

        foreach ($rows as $row) {
            $headKey = $row['fee_head_id'].'|'.($row['source_period_no'] ?? 'ONE_TIME');
            if (isset($seenHeads[$headKey])) {
                throw ValidationException::withMessages([
                    'fee' => 'Duplicate effective Fee Head detected between '.$seenHeads[$headKey].' and '.$row['structure_name'].'. Resolve Fee Foundation overlap before generating demand.',
                ]);
            }
            $seenHeads[$headKey] = $row['structure_name'];

            $alreadyDemanded = FeeDemandItem::query()
                ->where('fee_structure_item_id', $row['fee_structure_item_id'])
                ->where(function ($q) use ($row) {
                    if ($row['source_period_no'] === null) {
                        $q->whereNull('source_period_no');
                    } else {
                        $q->where('source_period_no', $row['source_period_no']);
                    }
                })
                ->whereHas('demand', fn ($q) => $q
                    ->where('admission_id', $admission->id)
                    ->where('status', '!=', 'CANCELLED'))
                ->exists();

            if (! $alreadyDemanded) {
                $result[] = $row;
            }
        }

        return $result;
    }

    private function persistDemand(
        College $college,
        Admission $admission,
        CollegeProgramOffering $offering,
        int $periodNo,
        array $snapshots,
        int $userId,
        string $generationMode,
        string $demandContext,
        string $basisGroup,
        string $periodLabel,
        ?string $bulkRunKey
    ): FeeDemand {
        if (! $snapshots) {
            throw ValidationException::withMessages(['fee' => 'No new applicable fee items remain for this demand context.']);
        }

        return DB::transaction(function () use ($college, $admission, $offering, $periodNo, $snapshots, $userId, $generationMode, $demandContext, $basisGroup, $periodLabel, $bulkRunKey) {
            $locked = Admission::query()->lockForUpdate()->findOrFail($admission->id);
            if ($locked->college_id !== $college->id || $locked->status !== 'CONFIRMED') {
                throw ValidationException::withMessages(['admission' => 'Admission is no longer CONFIRMED for this College.']);
            }

            $currencies = collect($snapshots)->pluck('currency')->filter()->unique();
            if ($currencies->isEmpty()) {
                $currencies = collect($snapshots)->map(fn ($row) => $this->structureCurrency((int) $row['fee_structure_id']))->unique();
            }
            if ($currencies->count() !== 1) {
                throw ValidationException::withMessages(['fee' => 'Applicable fee structures use different currencies. One demand cannot mix currencies.']);
            }

            // Re-check source-item uniqueness under the transaction to protect against duplicate callbacks/actions.
            foreach ($snapshots as $row) {
                $query = FeeDemandItem::query()
                    ->where('fee_structure_item_id', $row['fee_structure_item_id'])
                    ->whereHas('demand', fn ($q) => $q->where('admission_id', $locked->id)->where('status', '!=', 'CANCELLED'));
                $row['source_period_no'] === null
                    ? $query->whereNull('source_period_no')
                    : $query->where('source_period_no', $row['source_period_no']);
                if ($query->exists()) {
                    throw ValidationException::withMessages(['fee' => 'One or more fee items were already demanded for this admission and billing coverage. Refresh and retry.']);
                }
            }

            $total = collect($snapshots)->sum('amount');
            $mandatory = collect($snapshots)->where('is_mandatory', true)->sum('amount');
            $clearance = collect($snapshots)->where('is_enrollment_clearance_required', true)->sum('amount');

            $demand = FeeDemand::create([
                'university_id' => $college->university_id,
                'college_id' => $college->id,
                'admission_id' => $locked->id,
                'college_program_offering_id' => $offering->id,
                'academic_session_id' => $offering->academic_session_id,
                'curriculum_id' => $locked->curriculum_id ?: $offering->curriculum_id,
                'billing_period_no' => $periodNo,
                'demand_no' => 'FD-'.$college->id.'-'.$locked->id.'-'.strtoupper(substr($demandContext, 0, 3)).'-P'.$periodNo.'-'.strtoupper(substr((string) Str::uuid(), 0, 8)),
                'currency' => $currencies->first(),
                'total_amount' => $total,
                'mandatory_amount' => $mandatory,
                'enrollment_clearance_amount' => $clearance,
                'outstanding_amount' => $total,
                'status' => 'OPEN',
                'generation_mode' => $generationMode,
                'demand_context' => $demandContext,
                'billing_basis_group' => $basisGroup,
                'billing_period_label' => $periodLabel,
                'bulk_run_key' => $bulkRunKey,
                'generated_at' => now(),
                'generated_by' => $userId,
            ]);

            foreach ($snapshots as $i => $row) {
                unset($row['currency']);
                $demand->items()->create($row + ['display_order' => $i + 1]);
            }

            return $demand->load('items');
        });
    }

    private function structureCurrency(int $structureId): string
    {
        return (string) FeeStructure::query()->whereKey($structureId)->value('currency');
    }

    private function effectiveStructures(College $college, int $offeringId, int $sessionId, int $programId, ?int $curriculumId): Collection
    {
        $with = ['items.head', 'items.periodAmounts', 'items.periodExclusions', 'items.periodSettings', 'programTemplate', 'curriculum.terms', 'offering.programTemplate', 'offering.curriculum.terms'];

        $collegeRows = FeeStructure::with($with)
            ->where('college_id', $college->id)
            ->where('college_program_offering_id', $offeringId)
            ->where('academic_session_id', $sessionId)
            ->where('status', 'ACTIVE')
            ->get();

        $university = FeeStructure::with($with)
            ->where('university_id', $college->university_id)
            ->whereNull('college_id')
            ->where('academic_session_id', $sessionId)
            ->where('status', 'ACTIVE')
            ->where(function ($q) use ($programId) {
                $q->whereNull('program_template_id')->orWhere('program_template_id', $programId);
            })
            ->where(function ($q) use ($curriculumId) {
                $q->whereNull('curriculum_id');
                if ($curriculumId) {
                    $q->orWhere('curriculum_id', $curriculumId);
                }
            })
            ->get()
            ->filter(function ($structure) use ($college) {
                if ($structure->college_applicability === 'MANDATORY') {
                    return true;
                }
                if ($structure->college_applicability !== 'OPTIONAL') {
                    return false;
                }

                return CollegeFeeStructureAdoption::query()
                    ->where('college_id', $college->id)
                    ->where('university_fee_structure_id', $structure->id)
                    ->where('status', 'ADOPTED')
                    ->exists();
            });

        return $university->concat($collegeRows)->values();
    }

    private function effectiveItems(FeeStructure $structure, int $periodNo): array
    {
        $basis = $structure->charge_basis;
        if ($basis === 'ONE_TIME' && $periodNo !== 1) {
            return [];
        }
        if (in_array($basis, ['SPECIFIC_TERM', 'SPECIFIC_ACADEMIC_YEAR'], true)
            && (int) $structure->charge_period_no !== $periodNo) {
            return [];
        }

        $rows = [];
        foreach ($structure->items as $item) {
            if ($item->status !== 'ACTIVE' || ! $item->head || $item->head->status !== 'ACTIVE') {
                continue;
            }

            if ($basis === 'ONE_TIME') {
                $amount = (float) $item->amount;
                $setting = [
                    'due_date' => $item->due_date?->format('Y-m-d'),
                    'is_mandatory' => (bool) $item->is_mandatory,
                    'is_enrollment_clearance_required' => (bool) $item->is_enrollment_clearance_required,
                    'installment_allowed' => (bool) $item->installment_allowed,
                    'is_refundable' => (bool) $item->head->is_refundable,
                ];
                $sourcePeriod = null;
            } elseif (in_array($basis, ['SPECIFIC_TERM', 'SPECIFIC_ACADEMIC_YEAR'], true)) {
                // Specific-period structures are item-level policies scoped by the structure's exact period.
                $amount = (float) $item->amount;
                $setting = [
                    'due_date' => $item->due_date?->format('Y-m-d'),
                    'is_mandatory' => (bool) $item->is_mandatory,
                    'is_enrollment_clearance_required' => (bool) $item->is_enrollment_clearance_required,
                    'installment_allowed' => (bool) $item->installment_allowed,
                    'is_refundable' => (bool) $item->head->is_refundable,
                ];
                $sourcePeriod = $periodNo;
            } else {
                if ($item->periodExclusions->contains(fn ($x) => (int) $x->period_no === $periodNo)) {
                    continue;
                }
                $periodSetting = $item->periodSettings->first(fn ($x) => (int) $x->period_no === $periodNo);
                $periodAmount = $item->periodAmounts->first(fn ($x) => (int) $x->period_no === $periodNo);
                if (! $periodSetting || $periodSetting->status !== 'ACTIVE' || ! $periodAmount) {
                    continue;
                }
                $amount = (float) $periodAmount->amount;
                $setting = [
                    'due_date' => $periodSetting->due_date?->format('Y-m-d'),
                    'is_mandatory' => (bool) $periodSetting->is_mandatory,
                    'is_enrollment_clearance_required' => (bool) $periodSetting->is_enrollment_clearance_required,
                    'installment_allowed' => (bool) $periodSetting->installment_allowed,
                    'is_refundable' => (bool) $item->head->is_refundable,
                ];
                $sourcePeriod = $periodNo;
            }

            if ($amount <= 0) {
                continue;
            }
            if (empty($setting['due_date'])) {
                throw ValidationException::withMessages([
                    'fee' => 'Fee Setup is incomplete: '.$item->head->name.' in '.$structure->name.' has no Standard Due Date for this Billing Period. Deactivate the Fee Structure, set the Due Date, and reactivate it before generating a new demand.',
                ]);
            }

            $rows[] = [
                'fee_structure_id' => $structure->id,
                'fee_structure_item_id' => $item->id,
                'fee_head_id' => $item->fee_head_id,
                'source_period_no' => $sourcePeriod,
                'owner_type' => $structure->college_id ? 'COLLEGE' : 'UNIVERSITY',
                'structure_name' => $structure->name,
                'structure_code' => $structure->code,
                'fee_head_name' => $item->head->name,
                'fee_head_code' => $item->head->code,
                'purpose' => $structure->purpose,
                'charge_basis' => $basis,
                'amount' => $amount,
                'currency' => $structure->currency,
            ] + $setting;
        }

        return $rows;
    }

    private function periodsForStructure(FeeStructure $structure, CollegeProgramOffering $offering): array
    {
        $basis = $structure->charge_basis;
        if ($basis === 'ONE_TIME') {
            return [1];
        }
        if (in_array($basis, ['SPECIFIC_TERM', 'SPECIFIC_ACADEMIC_YEAR'], true)) {
            return $structure->charge_period_no ? [(int) $structure->charge_period_no] : [];
        }

        $offering->loadMissing(['programTemplate', 'curriculum.terms']);
        $terms = $offering->curriculum?->terms?->where('status', 'ACTIVE')
            ->pluck('sequence_no')->map(fn ($n) => (int) $n)->unique()->sort()->values() ?? collect();

        if ($basis === 'PER_TERM') {
            return $terms->all();
        }

        if ($basis === 'PER_ACADEMIC_YEAR') {
            $duration = max(1, (int) $offering->programTemplate?->duration_terms);
            $termsPerYear = match (strtoupper((string) $offering->programTemplate?->term_structure)) {
                'SEMESTER' => 2,
                'TRIMESTER' => 3,
                default => 1,
            };
            $years = [];
            foreach (range(1, (int) ceil($duration / $termsPerYear)) as $year) {
                $start = (($year - 1) * $termsPerYear) + 1;
                $end = min($year * $termsPerYear, $duration);
                $complete = true;
                foreach (range($start, $end) as $sequence) {
                    if (! $terms->contains($sequence)) {
                        $complete = false;
                        break;
                    }
                }
                if ($complete) {
                    $years[] = $year;
                }
            }
            return $years;
        }

        return [];
    }

    private function basisGroup(string $basis): string
    {
        return match ($basis) {
            'PER_TERM', 'SPECIFIC_TERM' => 'TERM',
            'PER_ACADEMIC_YEAR', 'SPECIFIC_ACADEMIC_YEAR' => 'ACADEMIC_YEAR',
            default => 'ONE_TIME',
        };
    }

    private function periodLabel(CollegeProgramOffering $offering, string $group, int $periodNo): string
    {
        if ($group === 'ACADEMIC_YEAR') {
            return 'Academic Year '.$periodNo;
        }
        if ($group === 'ONE_TIME') {
            return 'One-Time Charge';
        }

        $termStructure = strtoupper((string) $offering->programTemplate?->term_structure);
        $prefix = match ($termStructure) {
            'SEMESTER' => 'Semester',
            'TRIMESTER' => 'Trimester',
            default => 'Term',
        };

        $termName = $offering->curriculum?->terms?->first(fn ($term) => (int) $term->sequence_no === $periodNo && $term->status === 'ACTIVE')?->name;
        return $termName ?: $prefix.' '.$periodNo;
    }
}
