<?php

namespace App\Services;

use App\Models\AcademicSession;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Models\Curriculum;
use App\Models\CollegeFeeStructureAdoption;
use App\Models\FeeCategory;
use App\Models\FeeHead;
use App\Models\FeeStructure;
use App\Models\FeeStructureItem;
use App\Models\FeeStructureItemPeriodAmount;
use App\Models\FeeStructureItemPeriodExclusion;
use App\Models\ProgramTemplate;
use App\Models\University;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FeeFoundationService
{
    public function createCategory(University $university, ?College $college, array $data, int $actorId, ?string $ip): FeeCategory
    {
        $this->assertOwnerActive($university, $college);
        $this->assertCategoryCodeUnique($university->id, $data['code']);

        return DB::transaction(function () use ($university, $college, $data, $actorId, $ip) {
            $category = FeeCategory::create([
                'university_id' => $university->id,
                'college_id' => $college?->id,
                'name' => trim($data['name']),
                'code' => strtoupper(trim($data['code'])),
                'description' => $data['description'] ?? null,
                'display_order' => (int) ($data['display_order'] ?? 0),
                'status' => 'INACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $this->audit($college ? 'COLLEGE_FEE_CATEGORY_CREATED' : 'UNIVERSITY_FEE_CATEGORY_CREATED', 'FeeCategory', $category->id, $college, $actorId, $ip, null, $category->toArray());
            return $category;
        });
    }

    public function updateCategory(FeeCategory $category, University $university, ?College $college, array $data, int $actorId, ?string $ip): void
    {
        $this->assertCategoryOwned($category, $university, $college);
        $this->assertOwnerActive($university, $college);
        $this->assertCategoryCodeUnique($university->id, $data['code'], $category->id);
        if ($category->status === 'ACTIVE' && $category->heads()->where('status', 'ACTIVE')->exists()) {
            throw ValidationException::withMessages(['category' => 'An ACTIVE Fee Category used by an ACTIVE Fee Head cannot be edited. Deactivate the Fee Head first.']);
        }
        $before = $category->toArray();
        $category->update([
            'name' => trim($data['name']),
            'code' => strtoupper(trim($data['code'])),
            'description' => $data['description'] ?? null,
            'display_order' => (int) ($data['display_order'] ?? 0),
            'updated_by' => $actorId,
        ]);
        $this->audit($college ? 'COLLEGE_FEE_CATEGORY_UPDATED' : 'UNIVERSITY_FEE_CATEGORY_UPDATED', 'FeeCategory', $category->id, $college, $actorId, $ip, $before, $category->fresh()->toArray());
    }

    public function changeCategoryStatus(FeeCategory $category, University $university, ?College $college, string $status, int $actorId, ?string $ip): void
    {
        $this->assertCategoryOwned($category, $university, $college);
        $this->assertOwnerActive($university, $college);
        if ($category->status === $status) return;
        if ($status === 'INACTIVE' && $category->heads()->where('status', 'ACTIVE')->exists()) {
            throw ValidationException::withMessages(['status' => 'This Fee Category is used by an ACTIVE Fee Head and cannot be deactivated.']);
        }
        $before = ['status' => $category->status];
        $category->update(['status' => $status, 'updated_by' => $actorId]);
        $this->audit(($college ? 'COLLEGE' : 'UNIVERSITY').'_FEE_CATEGORY_'.($status === 'ACTIVE' ? 'ACTIVATED' : 'DEACTIVATED'), 'FeeCategory', $category->id, $college, $actorId, $ip, $before, ['status' => $status]);
    }

    public function createHead(University $university, ?College $college, array $data, int $actorId, ?string $ip): FeeHead
    {
        $this->assertOwnerActive($university, $college);
        $this->assertHeadCodeUnique($university->id, $college?->id, $data['code']);
        $category = $this->resolveCategory($university, $college, (int) $data['fee_category_id']);

        return DB::transaction(function () use ($university, $college, $data, $category, $actorId, $ip) {
            $head = FeeHead::create([
                'university_id' => $university->id,
                'college_id' => $college?->id,
                'name' => trim($data['name']),
                'code' => strtoupper(trim($data['code'])),
                'fee_category_id' => $category->id,
                'description' => $data['description'] ?? null,
                'is_refundable' => (bool) ($data['is_refundable'] ?? false),
                'status' => 'INACTIVE',
                'created_by' => $actorId,
                'updated_by' => $actorId,
            ]);
            $this->audit($college ? 'COLLEGE_FEE_HEAD_CREATED' : 'UNIVERSITY_FEE_HEAD_CREATED', 'FeeHead', $head->id, $college, $actorId, $ip, null, $head->toArray());
            return $head;
        });
    }

    public function updateHead(FeeHead $head, University $university, ?College $college, array $data, int $actorId, ?string $ip): void
    {
        $this->assertHeadOwned($head, $university, $college);
        $this->assertOwnerActive($university, $college);
        $this->assertHeadCodeUnique($university->id, $college?->id, $data['code'], $head->id);
        $category = $this->resolveCategory($university, $college, (int) $data['fee_category_id']);
        if ($head->status === 'ACTIVE' && FeeStructureItem::where('fee_head_id', $head->id)->whereHas('structure', fn ($q) => $q->where('status', 'ACTIVE'))->exists()) {
            throw ValidationException::withMessages(['head' => 'An ACTIVE Fee Head used by an ACTIVE Fee Structure cannot be edited. Deactivate the structure first.']);
        }
        DB::transaction(function () use ($head, $data, $category, $college, $actorId, $ip) {
            $before = $head->toArray();
            $head->update([
                'name' => trim($data['name']), 'code' => strtoupper(trim($data['code'])), 'fee_category_id' => $category->id,
                'description' => $data['description'] ?? null, 'is_refundable' => (bool) ($data['is_refundable'] ?? false), 'updated_by' => $actorId,
            ]);
            $this->audit($college ? 'COLLEGE_FEE_HEAD_UPDATED' : 'UNIVERSITY_FEE_HEAD_UPDATED', 'FeeHead', $head->id, $college, $actorId, $ip, $before, $head->fresh()->toArray());
        });
    }

    public function changeHeadStatus(FeeHead $head, University $university, ?College $college, string $status, int $actorId, ?string $ip): void
    {
        $this->assertHeadOwned($head, $university, $college);
        $this->assertOwnerActive($university, $college);
        if ($head->status === $status) return;
        if ($status === 'ACTIVE') {
            $category = $head->category;
            if (! $category || $category->status !== 'ACTIVE') {
                throw ValidationException::withMessages(['status' => 'Activate the selected Fee Category before activating this Fee Head.']);
            }
        }
        if ($status === 'INACTIVE' && FeeStructureItem::where('fee_head_id', $head->id)->where('status', 'ACTIVE')->whereHas('structure', fn ($q) => $q->where('status', 'ACTIVE'))->exists()) {
            throw ValidationException::withMessages(['status' => 'This Fee Head is used by an ACTIVE Fee Structure and cannot be deactivated.']);
        }
        $before = ['status' => $head->status];
        $head->update(['status' => $status, 'updated_by' => $actorId]);
        $this->audit(($college ? 'COLLEGE' : 'UNIVERSITY').'_FEE_HEAD_'.($status === 'ACTIVE' ? 'ACTIVATED' : 'DEACTIVATED'), 'FeeHead', $head->id, $college, $actorId, $ip, $before, ['status' => $status]);
    }

    public function createStructure(University $university, ?College $college, array $data, int $actorId, ?string $ip): FeeStructure
    {
        $this->assertOwnerActive($university, $college);
        [$sessionId, $programTemplateId, $offeringId, $curriculumId] = $this->resolveStructureScope($university, $college, $data);
        $this->assertStructureCodeUnique($university->id, $college?->id, $data['code']);
        [$chargeBasis, $chargePeriodNo] = $this->resolveChargePeriod($university, $college, $data, $programTemplateId, $offeringId);

        return DB::transaction(function () use ($university, $college, $data, $sessionId, $programTemplateId, $offeringId, $curriculumId, $chargeBasis, $chargePeriodNo, $actorId, $ip) {
            $structure = FeeStructure::create([
                'university_id' => $university->id, 'college_id' => $college?->id, 'college_program_offering_id' => $offeringId,
                'program_template_id' => $programTemplateId, 'curriculum_id' => $curriculumId, 'academic_session_id' => $sessionId,
                'name' => trim($data['name']), 'code' => strtoupper(trim($data['code'])), 'purpose' => $data['purpose'],
                'charge_basis' => $chargeBasis, 'charge_period_no' => $chargePeriodNo,
                'college_applicability' => $college ? null : ($data['college_applicability'] ?? 'OPTIONAL'),
                'currency' => strtoupper($data['currency'] ?? 'INR'), 'status' => 'INACTIVE', 'notes' => $data['notes'] ?? null,
                'created_by' => $actorId, 'updated_by' => $actorId,
            ]);
            $this->audit($college ? 'COLLEGE_FEE_STRUCTURE_CREATED' : 'UNIVERSITY_FEE_STRUCTURE_CREATED', 'FeeStructure', $structure->id, $college, $actorId, $ip, null, $structure->toArray());
            return $structure;
        });
    }

    public function updateStructure(FeeStructure $structure, University $university, ?College $college, array $data, int $actorId, ?string $ip): void
    {
        $this->assertStructureOwned($structure, $university, $college);
        $this->assertOwnerActive($university, $college);
        if ($structure->status === 'ACTIVE') {
            throw ValidationException::withMessages(['structure' => 'Deactivate this Fee Structure before changing its scope or details.']);
        }
        [$sessionId, $programTemplateId, $offeringId, $curriculumId] = $this->resolveStructureScope($university, $college, $data);
        $this->assertStructureCodeUnique($university->id, $college?->id, $data['code'], $structure->id);
        [$chargeBasis, $chargePeriodNo] = $this->resolveChargePeriod($university, $college, $data, $programTemplateId, $offeringId);

        $billingContextChanged = $structure->charge_basis !== $chargeBasis
            || (int) ($structure->charge_period_no ?? 0) !== (int) ($chargePeriodNo ?? 0)
            || (int) ($structure->academic_session_id ?? 0) !== (int) ($sessionId ?? 0)
            || (int) ($structure->college_program_offering_id ?? 0) !== (int) ($offeringId ?? 0)
            || (int) ($structure->program_template_id ?? 0) !== (int) ($programTemplateId ?? 0)
            || (int) ($structure->curriculum_id ?? 0) !== (int) ($curriculumId ?? 0);

        if ($billingContextChanged && $structure->items()->exists()) {
            throw ValidationException::withMessages([
                'charge_basis' => 'Billing scope and Fee Collection Basis cannot be changed while Fee Items are configured. Remove the Fee Items first, then change the billing scope/basis and add only the charges that apply to the new Billing Periods.',
            ]);
        }

        $before = $structure->toArray();
        DB::transaction(function () use ($structure, $data, $sessionId, $programTemplateId, $offeringId, $curriculumId, $chargeBasis, $chargePeriodNo, $college, $actorId) {
            $structure->update([
                'college_program_offering_id' => $offeringId, 'program_template_id' => $programTemplateId, 'curriculum_id' => $curriculumId, 'academic_session_id' => $sessionId,
                'name' => trim($data['name']), 'code' => strtoupper(trim($data['code'])), 'purpose' => $data['purpose'],
                'charge_basis' => $chargeBasis, 'charge_period_no' => $chargePeriodNo,
                'college_applicability' => $college ? null : ($data['college_applicability'] ?? 'OPTIONAL'),
                'currency' => strtoupper($data['currency'] ?? 'INR'), 'notes' => $data['notes'] ?? null, 'updated_by' => $actorId,
            ]);
        });
        $this->audit($college ? 'COLLEGE_FEE_STRUCTURE_UPDATED' : 'UNIVERSITY_FEE_STRUCTURE_UPDATED', 'FeeStructure', $structure->id, $college, $actorId, $ip, $before, $structure->fresh()->toArray());
    }

    public function changeStructureStatus(FeeStructure $structure, University $university, ?College $college, string $status, int $actorId, ?string $ip): void
    {
        $this->assertStructureOwned($structure, $university, $college);
        $this->assertOwnerActive($university, $college);
        if ($structure->status === $status) return;
        if ($status === 'ACTIVE') {
            $items = $structure->items()->with(['head', 'periodAmounts', 'periodExclusions', 'periodSettings'])->get();
            $recurring = in_array($structure->charge_basis, ['PER_TERM', 'PER_ACADEMIC_YEAR'], true);
            $periods = $recurring ? $this->availableChargePeriods($structure)->map(fn ($n) => (int) $n)->values() : collect([null]);
            $activeItems = $items->filter(function ($item) use ($recurring, $periods) {
                foreach ($periods as $periodNo) {
                    if ($recurring && $item->periodExclusions->contains(fn ($x) => (int) $x->period_no === (int) $periodNo)) continue;
                    $setting = $recurring ? $item->periodSettings->first(fn ($x) => (int) $x->period_no === (int) $periodNo) : null;
                    $status = $setting?->status ?? $item->status;
                    $override = $recurring ? $item->periodAmounts->first(fn ($x) => (int) $x->period_no === (int) $periodNo) : null;
                    $amount = (float) ($override?->amount ?? $item->amount);
                    if ($status === 'ACTIVE' && $amount > 0) return true;
                }
                return false;
            });
            if ($activeItems->isEmpty()) throw ValidationException::withMessages(['status' => 'Add at least one ACTIVE billing-period Fee Item with an amount greater than zero before activation.']);
            foreach ($activeItems as $item) {
                if ($item->head?->status !== 'ACTIVE') throw ValidationException::withMessages(['status' => 'All active billing-period Fee Items must use ACTIVE Fee Heads before this structure can be activated.']);
            }
            if ($college) {
                $offering = CollegeProgramOffering::whereKey($structure->college_program_offering_id)->where('college_id', $college->id)->where('status', 'ACTIVE')->first();
                if (! $offering) throw ValidationException::withMessages(['status' => 'The College Program Offering must be ACTIVE before its Fee Structure can be activated.']);
                if (in_array($structure->charge_basis, ['PER_TERM', 'PER_ACADEMIC_YEAR'], true) && $this->availableChargePeriods($structure)->isEmpty()) {
                    throw ValidationException::withMessages(['status' => 'The attached Curriculum does not yet contain the ACTIVE academic periods required by this Fee Collection Basis. Define the Curriculum periods first.']);
                }

                // A College structure may coexist as setup while INACTIVE, but it must
                // never become effective beside an effective University charge that
                // uses the same Fee Head over overlapping academic coverage.
                $this->assertNoEffectiveUniversityOverlapForCollegeActivation($structure, $college);
            } else {
                // Activating/re-activating a University policy can itself make a charge
                // effective for Colleges (MANDATORY, or OPTIONAL where adoption already
                // exists). Protect the invariant in this direction as well.
                $this->assertNoActiveCollegeOverlapForUniversityActivation($structure);
            }
            $duplicate = FeeStructure::query()->where('id', '!=', $structure->id)->where('university_id', $university->id)
                ->where('purpose', $structure->purpose)->where('academic_session_id', $structure->academic_session_id)
                ->where('charge_basis', $structure->charge_basis)->where('status', 'ACTIVE');
            $structure->charge_period_no ? $duplicate->where('charge_period_no', $structure->charge_period_no) : $duplicate->whereNull('charge_period_no');
            if ($college) $duplicate->where('college_id', $college->id)->where('college_program_offering_id', $structure->college_program_offering_id);
            else {
                $duplicate->whereNull('college_id');
                $structure->program_template_id ? $duplicate->where('program_template_id', $structure->program_template_id) : $duplicate->whereNull('program_template_id');
            }
            if ($duplicate->exists()) throw ValidationException::withMessages(['status' => 'Another ACTIVE Fee Structure already exists for the same owner, session, purpose and academic scope.']);
        }
        $before = ['status' => $structure->status];
        $structure->update(['status' => $status, 'updated_by' => $actorId]);
        $this->audit(($college ? 'COLLEGE' : 'UNIVERSITY').'_FEE_STRUCTURE_'.($status === 'ACTIVE' ? 'ACTIVATED' : 'DEACTIVATED'), 'FeeStructure', $structure->id, $college, $actorId, $ip, $before, ['status' => $status]);
    }

    public function upsertItem(FeeStructure $structure, ?FeeStructureItem $item, University $university, ?College $college, array $data, int $actorId, ?string $ip): FeeStructureItem
    {
        $this->assertStructureOwned($structure, $university, $college);
        $this->assertOwnerActive($university, $college);
        if ($structure->status === 'ACTIVE') throw ValidationException::withMessages(['item' => 'Deactivate the Fee Structure before changing Fee Items.']);
        if ($item && $item->fee_structure_id !== $structure->id) abort(404);
        $head = FeeHead::whereKey($data['fee_head_id'])
            ->where('university_id', $university->id)
            ->when(
                $college,
                fn ($q) => $q->where(function ($scope) use ($college) {
                    $scope->whereNull('college_id')->orWhere('college_id', $college->id);
                }),
                fn ($q) => $q->whereNull('college_id')
            )
            ->first();
        if (! $head || $head->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'fee_head_id' => $college
                    ? 'Select an ACTIVE University Fee Head or an ACTIVE Fee Head owned by this College.'
                    : 'Select an ACTIVE Fee Head owned by this University.',
            ]);
        }
        if (FeeStructureItem::where('fee_structure_id', $structure->id)->where('fee_head_id', $head->id)->when($item, fn ($q) => $q->whereKeyNot($item->id))->exists()) {
            throw ValidationException::withMessages(['fee_head_id' => 'This Fee Head is already present in the structure.']);
        }

        [$periodAmounts, $periodExclusions] = $this->normalizePeriodConfiguration(
            $structure,
            $data['period_amounts'] ?? [],
            $data['period_applicable'] ?? []
        );
        $periodSettings = $this->normalizePeriodSettings($structure, $data['period_settings'] ?? []);
        if ($college) {
            $this->assertNoEffectiveUniversityFeeHeadOverlap($structure, $head, $periodExclusions, $periodSettings, $college);
        }
        $before = $item?->load(['periodAmounts', 'periodExclusions', 'periodSettings'])->toArray();
        $values = [
            'fee_structure_id' => $structure->id, 'fee_head_id' => $head->id, 'amount' => $data['amount'],
            'is_mandatory' => (bool) ($data['is_mandatory'] ?? false),
            'is_enrollment_clearance_required' => (bool) ($data['is_enrollment_clearance_required'] ?? false),
            'installment_allowed' => (bool) ($data['installment_allowed'] ?? false),
            'display_order' => (int) ($data['display_order'] ?? 0), 'status' => $data['status'] ?? 'ACTIVE', 'updated_by' => $actorId,
        ];

        DB::transaction(function () use (&$item, $values, $actorId, $periodAmounts, $periodExclusions, $periodSettings) {
            if ($item) $item->update($values); else $item = FeeStructureItem::create($values + ['created_by' => $actorId]);

            $item->periodAmounts()->delete();
            foreach ($periodAmounts as $periodNo => $amount) {
                $item->periodAmounts()->create([
                    'period_no' => $periodNo,
                    'amount' => $amount,
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                ]);
            }

            $item->periodExclusions()->delete();
            foreach ($periodExclusions as $periodNo) {
                $item->periodExclusions()->create([
                    'period_no' => $periodNo,
                    'created_by' => $actorId,
                ]);
                $item->periodSettings()->where('period_no', $periodNo)->delete();
            }

            foreach ($periodSettings as $periodNo => $setting) {
                if (in_array((int) $periodNo, $periodExclusions, true)) continue;
                $existing = $item->periodSettings()->where('period_no', $periodNo)->first();
                $item->periodSettings()->updateOrCreate(
                    ['period_no' => $periodNo],
                    $setting + [
                        'created_by' => $existing?->created_by ?? $actorId,
                        'updated_by' => $actorId,
                    ]
                );
            }
        });

        $this->audit(($college ? 'COLLEGE' : 'UNIVERSITY').'_FEE_STRUCTURE_ITEM_'.($before ? 'UPDATED' : 'CREATED'), 'FeeStructureItem', $item->id, $college, $actorId, $ip, $before, $item->fresh()->load(['periodAmounts', 'periodExclusions', 'periodSettings'])->toArray());
        return $item;
    }

    /**
     * Remove a Fee Head from one billing period.
     *
     * Recurring structures keep one shared FeeStructureItem across periods, so removing
     * a charge from a single period records that period as excluded and clears its
     * period-specific amount/settings. If no billing period remains applicable, the
     * shared item itself is deleted. ONE_TIME / SPECIFIC structures have only one
     * effective period, therefore the item is deleted directly.
     */
    public function removeItemFromPeriod(FeeStructure $structure, FeeStructureItem $item, University $university, ?College $college, ?int $periodNo, int $actorId, ?string $ip): void
    {
        $this->assertStructureOwned($structure, $university, $college);
        $this->assertOwnerActive($university, $college);
        if ($structure->status === 'ACTIVE') {
            throw ValidationException::withMessages(['item' => 'Deactivate the Fee Structure before removing Fee Items.']);
        }
        if ($item->fee_structure_id !== $structure->id) abort(404);

        $before = $item->load(['head', 'periodAmounts', 'periodExclusions', 'periodSettings'])->toArray();
        $recurring = in_array($structure->charge_basis, ['PER_TERM', 'PER_ACADEMIC_YEAR'], true);

        DB::transaction(function () use ($structure, $item, $periodNo, $actorId, $recurring) {
            if (! $recurring) {
                $item->periodSettings()->delete();
                $item->periodAmounts()->delete();
                $item->periodExclusions()->delete();
                $item->delete();
                return;
            }

            $available = $this->availableChargePeriods($structure)->map(fn ($n) => (int) $n)->values();
            if (! $periodNo || ! $available->contains((int) $periodNo)) {
                throw ValidationException::withMessages(['period_no' => 'Select a valid billing period for this Fee Item.']);
            }

            $periodNo = (int) $periodNo;
            $item->periodAmounts()->where('period_no', $periodNo)->delete();
            $item->periodSettings()->where('period_no', $periodNo)->delete();
            $item->periodExclusions()->updateOrCreate(
                ['period_no' => $periodNo],
                ['created_by' => $actorId]
            );

            $excluded = $item->periodExclusions()->pluck('period_no')->map(fn ($n) => (int) $n);
            $remaining = $available->reject(fn ($n) => $excluded->contains((int) $n));
            if ($remaining->isEmpty()) {
                $item->periodSettings()->delete();
                $item->periodAmounts()->delete();
                $item->periodExclusions()->delete();
                $item->delete();
            }
        });

        $this->audit(
            ($college ? 'COLLEGE' : 'UNIVERSITY').'_FEE_STRUCTURE_ITEM_REMOVED',
            'FeeStructureItem',
            $item->id,
            $college,
            $actorId,
            $ip,
            $before,
            [
                'removed_period_no' => $recurring ? (int) $periodNo : null,
                'item_deleted' => ! FeeStructureItem::whereKey($item->id)->exists(),
            ]
        );
    }



    /**
     * College-local charges may supplement an effective University structure, but they
     * may not charge the same Fee Head over the same academic coverage.
     */
    private function assertNoEffectiveUniversityFeeHeadOverlap(FeeStructure $localStructure, FeeHead $head, array $localExclusions, array $localSettings, College $college): void
    {
        $offering = CollegeProgramOffering::whereKey($localStructure->college_program_offering_id)->first();
        if (! $offering) return;

        $adoptedIds = CollegeFeeStructureAdoption::where('college_id', $college->id)
            ->where('status', 'ADOPTED')->pluck('university_fee_structure_id');

        $universityStructures = FeeStructure::query()
            ->where('university_id', $college->university_id)
            ->whereNull('college_id')
            ->where('status', 'ACTIVE')
            ->where('purpose', $localStructure->purpose)
            ->where('academic_session_id', $offering->academic_session_id)
            ->where(function ($q) use ($offering) {
                $q->whereNull('program_template_id')->orWhere('program_template_id', $offering->program_template_id);
            })
            ->where(function ($q) use ($offering) {
                $q->whereNull('curriculum_id')->orWhere('curriculum_id', $offering->curriculum_id);
            })
            ->where(function ($q) use ($adoptedIds) {
                $q->where('college_applicability', 'MANDATORY')
                    ->orWhere(function ($optional) use ($adoptedIds) {
                        $optional->where('college_applicability', 'OPTIONAL')->whereIn('id', $adoptedIds);
                    });
            })
            ->with(['programTemplate:id,term_structure,duration_terms', 'items' => fn ($q) => $q->where('fee_head_id', $head->id), 'items.periodExclusions', 'items.periodSettings'])
            ->get();

        if ($universityStructures->isEmpty()) return;

        $localCoverage = $this->configuredChargeCoverage($localStructure, $localExclusions, $localSettings);
        foreach ($universityStructures as $universityStructure) {
            foreach ($universityStructure->items as $universityItem) {
                $uniExclusions = $universityItem->periodExclusions->pluck('period_no')->map(fn ($n) => (int) $n)->all();
                $uniSettings = $universityItem->periodSettings->keyBy('period_no')->map(fn ($x) => ['status' => $x->status])->all();
                $universityCoverage = $this->configuredChargeCoverage($universityStructure, $uniExclusions, $uniSettings, $universityItem->status);
                if (array_intersect($localCoverage, $universityCoverage)) {
                    throw ValidationException::withMessages([
                        'fee_head_id' => "{$head->name} is already charged by the effective University Fee Structure '{$universityStructure->name}' for the same billing coverage. Add only genuinely additional College charges, or use a different Fee Head.",
                    ]);
                }
            }
        }
    }

    private function configuredChargeCoverage(FeeStructure $structure, array $exclusions, array $settings, string $fallbackStatus = 'ACTIVE'): array
    {
        if ($fallbackStatus !== 'ACTIVE') return [];
        if ($structure->charge_basis === 'ONE_TIME') return ['ONE_TIME'];

        $periods = $this->availableChargePeriods($structure)->map(fn ($n) => (int) $n)->values();
        $coverage = [];
        foreach ($periods as $periodNo) {
            if (in_array($periodNo, $exclusions, true)) continue;
            $setting = $settings[$periodNo] ?? $settings[(string) $periodNo] ?? null;
            if (($setting['status'] ?? 'ACTIVE') !== 'ACTIVE') continue;
            foreach ($this->academicCoverageForBillingPeriod($structure, $periodNo) as $token) $coverage[] = $token;
        }
        return array_values(array_unique($coverage));
    }

    private function academicCoverageForBillingPeriod(FeeStructure $structure, int $periodNo): array
    {
        if (in_array($structure->charge_basis, ['PER_TERM', 'SPECIFIC_TERM'], true)) return ['TERM:'.$periodNo];
        if (in_array($structure->charge_basis, ['PER_ACADEMIC_YEAR', 'SPECIFIC_ACADEMIC_YEAR'], true)) {
            $program = $structure->programTemplate ?: ProgramTemplate::find($structure->program_template_id);
            $term = strtoupper((string) ($program?->term_structure ?? 'YEAR'));
            $perYear = $term === 'SEMESTER' ? 2 : ($term === 'TRIMESTER' ? 3 : 1);
            $start = (($periodNo - 1) * $perYear) + 1;
            return array_map(fn ($n) => 'TERM:'.$n, range($start, $start + $perYear - 1));
        }
        return [];
    }

    /**
     * Final activation-time guard for College-local structures.
     *
     * Item-save validation is useful UX, but it is not sufficient because an OPTIONAL
     * University structure can be stopped, a conflicting local item can then be saved,
     * and the University structure can later be adopted again. Effectiveness is therefore
     * protected again at the lifecycle boundary where the College structure becomes ACTIVE.
     */
    private function assertNoEffectiveUniversityOverlapForCollegeActivation(FeeStructure $localStructure, College $college): void
    {
        $offering = CollegeProgramOffering::whereKey($localStructure->college_program_offering_id)->first();
        if (! $offering) return;

        $adoptedIds = CollegeFeeStructureAdoption::where('college_id', $college->id)
            ->where('status', 'ADOPTED')
            ->pluck('university_fee_structure_id');

        $universityStructures = FeeStructure::query()
            ->where('university_id', $college->university_id)
            ->whereNull('college_id')
            ->where('status', 'ACTIVE')
            ->where('purpose', $localStructure->purpose)
            ->where('academic_session_id', $offering->academic_session_id)
            ->where(function ($q) use ($offering) {
                $q->whereNull('program_template_id')->orWhere('program_template_id', $offering->program_template_id);
            })
            ->where(function ($q) use ($offering) {
                $q->whereNull('curriculum_id')->orWhere('curriculum_id', $offering->curriculum_id);
            })
            ->where(function ($q) use ($adoptedIds) {
                $q->where('college_applicability', 'MANDATORY')
                    ->orWhere(function ($optional) use ($adoptedIds) {
                        $optional->where('college_applicability', 'OPTIONAL')->whereIn('id', $adoptedIds);
                    });
            })
            ->get();

        foreach ($universityStructures as $universityStructure) {
            $conflict = $this->firstEffectiveStructureOverlap($universityStructure, $localStructure);
            if ($conflict) {
                throw ValidationException::withMessages([
                    'status' => "Cannot activate this College Fee Structure. {$conflict['head']} is already charged by the effective University Fee Structure '{$universityStructure->name}' over the same billing coverage.",
                ]);
            }
        }
    }

    /**
     * Guard University activation/re-activation. A MANDATORY structure becomes effective
     * for every matching College; an OPTIONAL structure becomes effective for Colleges
     * whose ADOPTED record already exists from an earlier lifecycle state.
     */
    private function assertNoActiveCollegeOverlapForUniversityActivation(FeeStructure $universityStructure): void
    {
        if (! in_array($universityStructure->college_applicability, ['MANDATORY', 'OPTIONAL'], true)) return;

        $collegeIds = null;
        if ($universityStructure->college_applicability === 'OPTIONAL') {
            $collegeIds = CollegeFeeStructureAdoption::where('university_fee_structure_id', $universityStructure->id)
                ->where('status', 'ADOPTED')
                ->pluck('college_id');
            if ($collegeIds->isEmpty()) return;
        }

        $this->assertNoActiveCollegeOverlapForUniversityStructure($universityStructure, $collegeIds, 'activation');
    }

    /**
     * Check an about-to-be-effective University structure against ACTIVE College-local
     * structures. `$collegeIds === null` means all matching Colleges (MANDATORY case).
     */
    private function assertNoActiveCollegeOverlapForUniversityStructure(FeeStructure $universityStructure, $collegeIds = null, string $context = 'activation'): void
    {
        $locals = FeeStructure::query()
            ->with(['college', 'offering'])
            ->where('university_id', $universityStructure->university_id)
            ->whereNotNull('college_id')
            ->where('status', 'ACTIVE')
            ->where('purpose', $universityStructure->purpose)
            ->where('academic_session_id', $universityStructure->academic_session_id)
            ->when($collegeIds !== null, fn ($q) => $q->whereIn('college_id', $collegeIds))
            ->get();

        foreach ($locals as $localStructure) {
            $offering = $localStructure->offering;
            if (! $offering || $offering->status !== 'ACTIVE') continue;
            if ($universityStructure->program_template_id && (int) $universityStructure->program_template_id !== (int) $offering->program_template_id) continue;
            if ($universityStructure->curriculum_id && (int) $universityStructure->curriculum_id !== (int) $offering->curriculum_id) continue;

            $conflict = $this->firstEffectiveStructureOverlap($universityStructure, $localStructure);
            if (! $conflict) continue;

            $collegeName = $localStructure->college?->name ?? ('College #'.$localStructure->college_id);
            $message = $context === 'adoption'
                ? "Cannot adopt this University Fee Structure. {$conflict['head']} is already charged by ACTIVE College Fee Structure '{$localStructure->name}' over the same billing coverage. Deactivate or remove the conflicting College charge first."
                : "Cannot activate this University Fee Structure. {$conflict['head']} conflicts with ACTIVE College Fee Structure '{$localStructure->name}' for {$collegeName} over the same billing coverage.";

            throw ValidationException::withMessages([
                $context === 'adoption' ? 'adoption' : 'status' => $message,
            ]);
        }
    }

    /**
     * Return the first same-Fee-Head overlap where both sides represent an ACTIVE,
     * positive-value charge over the same underlying academic coverage.
     */
    private function firstEffectiveStructureOverlap(FeeStructure $left, FeeStructure $right): ?array
    {
        $left->loadMissing(['programTemplate', 'curriculum.terms', 'offering.programTemplate', 'offering.curriculum.terms', 'items.head', 'items.periodAmounts', 'items.periodExclusions', 'items.periodSettings']);
        $right->loadMissing(['programTemplate', 'curriculum.terms', 'offering.programTemplate', 'offering.curriculum.terms', 'items.head', 'items.periodAmounts', 'items.periodExclusions', 'items.periodSettings']);

        $rightByHead = $right->items->groupBy('fee_head_id');
        foreach ($left->items as $leftItem) {
            foreach ($rightByHead->get($leftItem->fee_head_id, collect()) as $rightItem) {
                $overlap = array_values(array_intersect(
                    $this->effectiveItemCoverage($left, $leftItem),
                    $this->effectiveItemCoverage($right, $rightItem)
                ));
                if ($overlap) {
                    return [
                        'fee_head_id' => $leftItem->fee_head_id,
                        'head' => $leftItem->head?->name ?? ('Fee Head #'.$leftItem->fee_head_id),
                        'coverage' => $overlap[0],
                    ];
                }
            }
        }
        return null;
    }

    /**
     * Convert one persisted Fee Item into underlying academic coverage tokens.
     * Only ACTIVE + positive-value effective charges contribute to overlap.
     */
    private function effectiveItemCoverage(FeeStructure $structure, FeeStructureItem $item): array
    {
        if ($structure->charge_basis === 'ONE_TIME') {
            return $item->status === 'ACTIVE' && (float) $item->amount > 0 ? ['ONE_TIME'] : [];
        }

        if (in_array($structure->charge_basis, ['SPECIFIC_TERM', 'SPECIFIC_ACADEMIC_YEAR'], true)) {
            if ($item->status !== 'ACTIVE' || (float) $item->amount <= 0 || ! $structure->charge_period_no) return [];
            return $this->academicCoverageForBillingPeriod($structure, (int) $structure->charge_period_no);
        }

        if (! in_array($structure->charge_basis, ['PER_TERM', 'PER_ACADEMIC_YEAR'], true)) return [];

        $coverage = [];
        $periods = $this->availableChargePeriods($structure)->map(fn ($n) => (int) $n)->values();
        foreach ($periods as $periodNo) {
            if ($item->periodExclusions->contains(fn ($x) => (int) $x->period_no === $periodNo)) continue;

            $setting = $item->periodSettings->first(fn ($x) => (int) $x->period_no === $periodNo);
            $status = $setting?->status ?? $item->status;
            $amountRow = $item->periodAmounts->first(fn ($x) => (int) $x->period_no === $periodNo);
            $amount = (float) ($amountRow?->amount ?? $item->amount);
            if ($status !== 'ACTIVE' || $amount <= 0) continue;

            foreach ($this->academicCoverageForBillingPeriod($structure, $periodNo) as $token) {
                $coverage[] = $token;
            }
        }

        return array_values(array_unique($coverage));
    }

    public function setCollegeStructureAdoption(FeeStructure $structure, College $college, bool $adopt, int $actorId, ?string $ip): void
    {
        $university = $college->university;
        abort_unless($structure->university_id === $university->id && $structure->college_id === null, 404);
        $this->assertOwnerActive($university, $college);

        if ($structure->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['adoption' => 'Only an ACTIVE University Fee Structure can be adopted.']);
        }
        if ($structure->college_applicability === 'MANDATORY') {
            throw ValidationException::withMessages(['adoption' => 'This University Fee Structure is mandatory and already applies automatically; College adoption is not required.']);
        }
        if ($structure->college_applicability !== 'OPTIONAL') {
            throw ValidationException::withMessages(['adoption' => 'This University Fee Structure is not available for optional College adoption.']);
        }
        if (! $this->universityStructureAppliesToCollege($structure, $college)) {
            throw ValidationException::withMessages(['adoption' => 'This University Fee Structure does not match any Program Offering of this College for the configured Academic Session and Program scope.']);
        }

        if ($adopt) {
            // Adoption makes an OPTIONAL University structure effective immediately.
            // It must therefore be rejected when an already ACTIVE College structure
            // would create the same Fee Head over overlapping billing coverage.
            $this->assertNoActiveCollegeOverlapForUniversityStructure($structure, collect([$college->id]), 'adoption');
        }

        DB::transaction(function () use ($structure, $college, $adopt, $actorId, $ip) {
            $record = CollegeFeeStructureAdoption::firstOrNew([
                'university_fee_structure_id' => $structure->id,
                'college_id' => $college->id,
            ]);
            $before = $record->exists ? $record->toArray() : null;
            $record->status = $adopt ? 'ADOPTED' : 'NOT_ADOPTED';
            if (! $record->exists) $record->created_by = $actorId;
            $record->updated_by = $actorId;
            $record->save();

            $this->audit($adopt ? 'COLLEGE_UNIVERSITY_FEE_STRUCTURE_ADOPTED' : 'COLLEGE_UNIVERSITY_FEE_STRUCTURE_UNADOPTED', 'CollegeFeeStructureAdoption', $record->id, $college, $actorId, $ip, $before, $record->fresh()->toArray());
        });
    }

    public function universityStructureAppliesToCollege(FeeStructure $structure, College $college): bool
    {
        $query = CollegeProgramOffering::where('college_id', $college->id)
            ->where('status', 'ACTIVE')
            ->where('academic_session_id', $structure->academic_session_id);
        if ($structure->program_template_id) $query->where('program_template_id', $structure->program_template_id);
        if ($structure->curriculum_id) $query->where('curriculum_id', $structure->curriculum_id);
        return $query->exists();
    }

    private function resolveStructureScope(University $university, ?College $college, array $data): array
    {
        if ($college) {
            $offering = CollegeProgramOffering::with('academicSession')->whereKey($data['college_program_offering_id'] ?? null)->where('college_id', $college->id)->first();
            if (! $offering) throw ValidationException::withMessages(['college_program_offering_id' => 'Select a Program Offering belonging to this College.']);
            return [$offering->academic_session_id, $offering->program_template_id, $offering->id, $offering->curriculum_id];
        }
        $session = AcademicSession::whereKey($data['academic_session_id'] ?? null)->where('university_id', $university->id)->first();
        if (! $session) throw ValidationException::withMessages(['academic_session_id' => 'Select an Academic Session belonging to this University.']);
        $programId = $data['program_template_id'] ?? null;
        if ($programId && ! ProgramTemplate::whereKey($programId)->where('university_id', $university->id)->exists()) {
            throw ValidationException::withMessages(['program_template_id' => 'Select a Program Template belonging to this University.']);
        }
        $curriculumId = $data['curriculum_id'] ?? null;
        if ($curriculumId) {
            $curriculum = Curriculum::query()
                ->whereKey($curriculumId)
                ->where('university_id', $university->id)
                ->currentApproved()
                ->first();
            if (! $curriculum || (int) $curriculum->program_template_id !== (int) $programId || (int) $curriculum->academic_session_id !== (int) $session->id) {
                throw ValidationException::withMessages([
                    'curriculum_id' => 'Select the current ACTIVE and APPROVED Curriculum version for the selected Program and Academic Session. Previous or superseded Curriculum versions cannot be used for a new University Fee Structure.',
                ]);
            }
        }
        $basis = $data['charge_basis'] ?? 'ONE_TIME';
        if (in_array($basis, ['PER_TERM','PER_ACADEMIC_YEAR','SPECIFIC_TERM','SPECIFIC_ACADEMIC_YEAR'], true) && (! $programId || ! $curriculumId)) {
            throw ValidationException::withMessages(['curriculum_id' => 'University recurring/specific academic-period fees require the current ACTIVE and APPROVED Curriculum version. Fee periods must come from the selected Curriculum ACTIVE terms, not from a previous/superseded Curriculum or synthetic Program Template periods.']);
        }
        return [$session->id, $programId ?: null, null, $curriculumId ?: null];
    }

    private function resolveChargePeriod(University $university, ?College $college, array $data, ?int $programTemplateId, ?int $offeringId): array
    {
        $basis = $data['charge_basis'] ?? 'ONE_TIME';
        $periodNo = isset($data['charge_period_no']) && $data['charge_period_no'] !== '' ? (int) $data['charge_period_no'] : null;
        $allowed = ['ONE_TIME', 'PER_TERM', 'PER_ACADEMIC_YEAR', 'SPECIFIC_TERM', 'SPECIFIC_ACADEMIC_YEAR'];
        if (! in_array($basis, $allowed, true)) {
            throw ValidationException::withMessages(['charge_basis' => 'Select a valid Fee Collection Basis.']);
        }
        if (! in_array($basis, ['SPECIFIC_TERM', 'SPECIFIC_ACADEMIC_YEAR'], true)) return [$basis, null];
        if (! $programTemplateId) {
            throw ValidationException::withMessages(['charge_basis' => 'Select one Program scope before choosing a specific academic period.']);
        }

        $program = ProgramTemplate::whereKey($programTemplateId)->where('university_id', $university->id)->first();
        if (! $program) throw ValidationException::withMessages(['program_template_id' => 'The selected Program Template is not available.']);
        if (! $periodNo) throw ValidationException::withMessages(['charge_period_no' => 'Select the applicable academic period.']);

        $duration = max(1, (int) $program->duration_terms);
        if ($basis === 'SPECIFIC_TERM') {
            if ($periodNo > $duration) throw ValidationException::withMessages(['charge_period_no' => 'The selected academic term is outside this Program Template duration.']);
            $curriculum = $college && $offeringId
                ? CollegeProgramOffering::with('curriculum.terms')->whereKey($offeringId)->where('college_id', $college->id)->first()?->curriculum
                : Curriculum::with('terms')->whereKey($data['curriculum_id'] ?? null)->where('university_id', $university->id)->first();
            if (! $curriculum || ! $curriculum->terms->contains(fn ($term) => (int) $term->sequence_no === $periodNo && $term->status === 'ACTIVE')) {
                throw ValidationException::withMessages(['charge_period_no' => 'Select an ACTIVE academic term from the exact Curriculum configured for this Fee Structure.']);
            }
            return [$basis, $periodNo];
        }

        $termsPerYear = match (strtoupper((string) $program->term_structure)) { 'SEMESTER' => 2, 'TRIMESTER' => 3, default => 1 };
        $maxYears = (int) ceil($duration / $termsPerYear);
        if ($periodNo > $maxYears) throw ValidationException::withMessages(['charge_period_no' => 'The selected Academic Year is outside this Program Template duration.']);
        $curriculum = $college && $offeringId
            ? CollegeProgramOffering::with('curriculum.terms')->whereKey($offeringId)->where('college_id', $college->id)->first()?->curriculum
            : Curriculum::with('terms')->whereKey($data['curriculum_id'] ?? null)->where('university_id', $university->id)->first();
        $activeSequences = $curriculum?->terms?->where('status', 'ACTIVE')->pluck('sequence_no')->map(fn ($n) => (int) $n) ?? collect();
        $start = (($periodNo - 1) * $termsPerYear) + 1;
        $end = min($periodNo * $termsPerYear, $duration);
        foreach (range($start, $end) as $sequence) {
            if (! $activeSequences->contains($sequence)) {
                throw ValidationException::withMessages(['charge_period_no' => 'Select an Academic Year whose required terms are ACTIVE in the exact Curriculum configured for this Fee Structure.']);
            }
        }
        return [$basis, $periodNo];
    }

    private function normalizePeriodConfiguration(FeeStructure $structure, array $rawAmounts, array $rawApplicable): array
    {
        if (! in_array($structure->charge_basis, ['PER_TERM', 'PER_ACADEMIC_YEAR'], true)) {
            return [[], []];
        }

        $available = $this->availableChargePeriods($structure)->map(fn ($n) => (int) $n)->values();
        if ($available->isEmpty()) {
            return [[], []];
        }

        $periodAmounts = [];
        $periodExclusions = [];

        foreach ($rawApplicable as $period => $applicable) {
            $periodNo = (int) $period;
            if (! $available->contains($periodNo)) {
                throw ValidationException::withMessages(['period_applicable' => 'A period applicability value was supplied for an academic period that is not available in the applicable Program/Curriculum.']);
            }
            if (! filter_var($applicable, FILTER_VALIDATE_BOOLEAN)) {
                $periodExclusions[] = $periodNo;
            }
        }

        foreach ($rawAmounts as $period => $amount) {
            if ($amount === null || $amount === '') continue;
            $periodNo = (int) $period;
            if (! $available->contains($periodNo)) {
                throw ValidationException::withMessages(['period_amounts' => 'A period-specific fee amount was supplied for an academic period that is not available in the applicable Program/Curriculum.']);
            }
            if (in_array($periodNo, $periodExclusions, true)) {
                continue;
            }
            $numeric = (float) $amount;
            if ($numeric <= 0 || $numeric > 999999999.99) {
                throw ValidationException::withMessages(['period_amounts' => 'Period-specific amounts must be greater than zero and within the allowed fee limit.']);
            }
            $periodAmounts[$periodNo] = number_format($numeric, 2, '.', '');
        }

        if ($available->every(fn ($periodNo) => in_array((int) $periodNo, $periodExclusions, true))) {
            throw ValidationException::withMessages(['period_applicable' => 'This Fee Item must be applicable to at least one available academic period.']);
        }

        sort($periodExclusions);
        return [$periodAmounts, $periodExclusions];
    }

    private function normalizePeriodSettings(FeeStructure $structure, array $rawSettings): array
    {
        if (! in_array($structure->charge_basis, ['PER_TERM', 'PER_ACADEMIC_YEAR'], true) || empty($rawSettings)) {
            return [];
        }

        $available = $this->availableChargePeriods($structure)->map(fn ($n) => (int) $n)->values();
        $normalized = [];

        foreach ($rawSettings as $period => $setting) {
            $periodNo = (int) $period;
            if (! $available->contains($periodNo)) {
                throw ValidationException::withMessages(['period_settings' => 'Period-specific charge settings were supplied for an academic period that is not available in the applicable Curriculum.']);
            }
            if (! is_array($setting)) continue;
            $normalized[$periodNo] = [
                'is_mandatory' => filter_var($setting['is_mandatory'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'is_enrollment_clearance_required' => filter_var($setting['is_enrollment_clearance_required'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'installment_allowed' => filter_var($setting['installment_allowed'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'display_order' => max(0, min(65535, (int) ($setting['display_order'] ?? 0))),
                'status' => in_array(($setting['status'] ?? 'ACTIVE'), ['ACTIVE', 'INACTIVE'], true) ? $setting['status'] : 'ACTIVE',
            ];
        }

        return $normalized;
    }

    private function availableChargePeriods(FeeStructure $structure)
    {
        $structure->loadMissing(['programTemplate', 'curriculum.terms', 'offering.programTemplate', 'offering.curriculum.terms']);
        $program = $structure->programTemplate ?: $structure->offering?->programTemplate;
        if (! $program) return collect();

        $duration = max(1, (int) $program->duration_terms);
        $termStructure = strtoupper((string) $program->term_structure);
        $termsPerYear = match ($termStructure) { 'SEMESTER' => 2, 'TRIMESTER' => 3, default => 1 };

        if ($structure->college_id) {
            $terms = $structure->offering?->curriculum?->terms
                ?->where('status', 'ACTIVE')->pluck('sequence_no')->map(fn ($n) => (int) $n)->unique()->sort()->values() ?? collect();
            if ($structure->charge_basis === 'PER_TERM') return $terms;
            if ($structure->charge_basis === 'PER_ACADEMIC_YEAR') {
                $maxYears = (int) ceil($duration / $termsPerYear);
                return collect(range(1, $maxYears))->filter(function ($year) use ($terms, $termsPerYear, $duration) {
                    $start = (($year - 1) * $termsPerYear) + 1;
                    $end = min($year * $termsPerYear, $duration);
                    foreach (range($start, $end) as $sequence) if (! $terms->contains($sequence)) return false;
                    return true;
                })->values();
            }
            return collect();
        }

        if (! $structure->program_template_id || ! $structure->curriculum_id || ! $structure->curriculum) return collect();
        $terms = $structure->curriculum->terms->where('status','ACTIVE')->pluck('sequence_no')->map(fn ($n)=>(int)$n)->unique()->sort()->values();
        if ($structure->charge_basis === 'PER_TERM') return $terms;
        if ($structure->charge_basis === 'PER_ACADEMIC_YEAR') {
            $maxYears = (int) ceil($duration / $termsPerYear);
            return collect(range(1, $maxYears))->filter(function ($year) use ($terms, $termsPerYear, $duration) {
                $start=(($year-1)*$termsPerYear)+1; $end=min($year*$termsPerYear,$duration);
                foreach(range($start,$end) as $sequence) if(! $terms->contains($sequence)) return false;
                return true;
            })->values();
        }
        return collect();
    }

    private function assertOwnerActive(University $university, ?College $college): void
    {
        if ($college && $college->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['owner' => 'Fee Management cannot be changed while this College is inactive.']);
        }
    }
    private function resolveCategory(University $university, ?College $college, int $categoryId): FeeCategory
    {
        $category = FeeCategory::whereKey($categoryId)->where('university_id', $university->id)
            ->where(function ($q) use ($college) {
                $q->whereNull('college_id');
                if ($college) $q->orWhere('college_id', $college->id);
            })->first();
        if (! $category || $category->status !== 'ACTIVE') {
            throw ValidationException::withMessages(['fee_category_id' => 'Select an ACTIVE Fee Category available to this fee owner.']);
        }
        return $category;
    }

    private function assertCategoryOwned(FeeCategory $category, University $university, ?College $college): void
    {
        abort_unless($category->university_id === $university->id && (int) ($category->college_id ?? 0) === (int) ($college?->id ?? 0), 404);
    }

    private function assertCategoryCodeUnique(int $universityId, string $code, ?int $ignore = null): void
    {
        $q = FeeCategory::where('university_id', $universityId)->whereRaw('LOWER(code)=?', [strtolower(trim($code))]);
        if ($ignore) $q->whereKeyNot($ignore);
        if ($q->exists()) throw ValidationException::withMessages(['code' => 'This University already has a Fee Category with the same code. Category codes are shared across University and College fee setup.']);
    }

    private function assertHeadOwned(FeeHead $head, University $university, ?College $college): void { abort_unless($head->university_id === $university->id && (int) ($head->college_id ?? 0) === (int) ($college?->id ?? 0), 404); }
    private function assertStructureOwned(FeeStructure $structure, University $university, ?College $college): void { abort_unless($structure->university_id === $university->id && (int) ($structure->college_id ?? 0) === (int) ($college?->id ?? 0), 404); }
    private function assertHeadCodeUnique(int $universityId, ?int $collegeId, string $code, ?int $ignore = null): void
    {
        $q = FeeHead::where('university_id', $universityId)->whereRaw('LOWER(code)=?', [strtolower(trim($code))]);
        $collegeId ? $q->where('college_id', $collegeId) : $q->whereNull('college_id');
        if ($ignore) $q->whereKeyNot($ignore);
        if ($q->exists()) throw ValidationException::withMessages(['code' => 'This fee owner already has a Fee Head with the same code.']);
    }
    private function assertStructureCodeUnique(int $universityId, ?int $collegeId, string $code, ?int $ignore = null): void
    {
        $q = FeeStructure::where('university_id', $universityId)->whereRaw('LOWER(code)=?', [strtolower(trim($code))]);
        $collegeId ? $q->where('college_id', $collegeId) : $q->whereNull('college_id');
        if ($ignore) $q->whereKeyNot($ignore);
        if ($q->exists()) throw ValidationException::withMessages(['code' => 'This fee owner already has a Fee Structure with the same code.']);
    }
    private function audit(string $event, string $resourceType, int $resourceId, ?College $college, int $actorId, ?string $ip, ?array $before, ?array $after): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actorId, 'event' => $event, 'resource_type' => $resourceType, 'resource_id' => $resourceId,
            'scope_type' => $college ? 'COLLEGE' : 'UNIVERSITY', 'scope_reference' => $college ? 'college:'.$college->id : 'university',
            'before' => $before ? json_encode($before) : null, 'after' => $after ? json_encode($after) : null, 'ip_address' => $ip, 'created_at' => now(),
        ]);
    }
}
