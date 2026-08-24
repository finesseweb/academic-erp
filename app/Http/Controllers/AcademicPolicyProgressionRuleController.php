<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertAcademicPolicyProgressionRuleSetsRequest;
use App\Models\AcademicPolicy;
use App\Models\AcademicPolicyProgressionRuleSet;
use App\Models\Curriculum;
use App\Services\AcademicPolicyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcademicPolicyProgressionRuleController extends Controller
{
    public function __construct(private readonly AcademicPolicyService $policyService) {}

    public function edit(Request $request, AcademicPolicy $academicPolicy): Response
    {
        abort_unless($request->user()->hasPermission('academic_policy.view'), 403);

        $academicPolicy->load([
            'academicSession:id,name,code',
            'programTemplate:id,name,code',
            'curriculum:id,name,code,version',
            'progressionRuleSets.curriculum:id,name,code,version',
            'progressionRuleSets.targetTerm:id,curriculum_id,sequence_no,name,status',
            'progressionRuleSets.sourceTerms:id,curriculum_id,sequence_no,name,status',
        ]);

        return Inertia::render('admin/academic-policies/progression', [
            'policy' => $academicPolicy,
            'ruleSets' => $academicPolicy->progressionRuleSets,
            'availableCurricula' => $this->availableCurricula($academicPolicy),
            'cloneSources' => AcademicPolicy::query()
                ->where('university_id', $academicPolicy->university_id)
                ->whereKeyNot($academicPolicy->id)
                ->whereHas('progressionRuleSets')
                ->orderByDesc('id')
                ->get(['id', 'name', 'code', 'version', 'scope_type']),
            'editable' => $request->user()->hasPermission('academic_policy.update')
                && $academicPolicy->lifecycle_status === 'DRAFT'
                && ! in_array(
                    $academicPolicy->approval_status ?? 'NOT_SUBMITTED',
                    ['SUBMITTED', 'UNDER_APPROVAL', 'APPROVED'],
                    true
                ),
        ]);
    }

    public function update(
        UpsertAcademicPolicyProgressionRuleSetsRequest $request,
        AcademicPolicy $academicPolicy
    ): RedirectResponse {
        $this->policyService->assertEditable($academicPolicy);

        $data = $request->validated();
        $available = collect($this->availableCurricula($academicPolicy))
            ->keyBy(fn (array $curriculum) => (int) $curriculum['id']);

        $defaultRuleCount = collect($data['rule_sets'])
            ->where('applies_to_all_stages', true)
            ->count();

        if ($defaultRuleCount > 1) {
            throw ValidationException::withMessages([
                'rule_sets' => 'Only one Default / All Stages rule is allowed.',
            ]);
        }

        $usedTargets = [];

        foreach ($data['rule_sets'] as $index => &$ruleSet) {
            $allStages = (bool) $ruleSet['applies_to_all_stages'];

            if ($allStages) {
                $ruleSet['curriculum_id'] = null;
                $ruleSet['source_term_ids'] = [];
                $ruleSet['target_curriculum_term_id'] = null;
                continue;
            }

            $curriculumId = (int) ($ruleSet['curriculum_id'] ?? 0);
            if (! $curriculumId || ! $available->has($curriculumId)) {
                throw ValidationException::withMessages([
                    "rule_sets.$index.curriculum_id" =>
                        'Select a current applicable Curriculum for this progression rule.',
                ]);
            }

            $curriculum = $available->get($curriculumId);
            $termIds = collect($curriculum['terms'])->pluck('id')->map(fn ($id) => (int) $id);

            $sourceIds = collect($ruleSet['source_term_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values();

            if ($sourceIds->isEmpty()) {
                throw ValidationException::withMessages([
                    "rule_sets.$index.source_term_ids" =>
                        'Select at least one Term / Semester to evaluate.',
                ]);
            }

            if ($sourceIds->diff($termIds)->isNotEmpty()) {
                throw ValidationException::withMessages([
                    "rule_sets.$index.source_term_ids" =>
                        'One or more selected source Terms do not belong to the selected Curriculum.',
                ]);
            }

            $targetId = (int) ($ruleSet['target_curriculum_term_id'] ?? 0);
            if (! $targetId || ! $termIds->contains($targetId)) {
                throw ValidationException::withMessages([
                    "rule_sets.$index.target_curriculum_term_id" =>
                        'Select a valid Progress To Term from the selected Curriculum.',
                ]);
            }

            if ($sourceIds->contains($targetId)) {
                throw ValidationException::withMessages([
                    "rule_sets.$index.target_curriculum_term_id" =>
                        'Progress To Term cannot also be one of the Terms being evaluated.',
                ]);
            }

            $termMap = collect($curriculum['terms'])->keyBy(fn ($term) => (int) $term->id);
            $targetSequence = (int) $termMap->get($targetId)->sequence_no;
            $invalidSourceOrder = $sourceIds->contains(
                fn ($termId) => (int) $termMap->get($termId)->sequence_no >= $targetSequence
            );

            if ($invalidSourceOrder) {
                throw ValidationException::withMessages([
                    "rule_sets.$index.source_term_ids" =>
                        'Every evaluated Term must come before the Progress To Term in Curriculum order.',
                ]);
            }

            $targetKey = $curriculumId.':'.$targetId;
            if (isset($usedTargets[$targetKey])) {
                throw ValidationException::withMessages([
                    "rule_sets.$index.target_curriculum_term_id" =>
                        'Only one specific progression rule may target the same Curriculum Term.',
                ]);
            }
            $usedTargets[$targetKey] = true;

            if (
                $ruleSet['evaluation_mode'] === 'COMBINED'
                && $sourceIds->count() > 1
                && $ruleSet['minimum_sgpa'] !== null
            ) {
                throw ValidationException::withMessages([
                    "rule_sets.$index.minimum_sgpa" =>
                        'For a combined multi-term rule, use Minimum CGPA instead of Minimum SGPA.',
                ]);
            }
        }
        unset($ruleSet);

        DB::transaction(function () use ($academicPolicy, $data, $request) {
            $before = $academicPolicy->progressionRuleSets()
                ->with('sourceTerms:id,curriculum_id,sequence_no,name')
                ->get()
                ->toArray();

            $academicPolicy->progressionRuleSets()->delete();

            foreach (array_values($data['rule_sets']) as $index => $ruleSetData) {
                $sourceIds = $ruleSetData['source_term_ids'] ?? [];
                unset($ruleSetData['source_term_ids']);

                $ruleSet = AcademicPolicyProgressionRuleSet::query()->create([
                    ...$ruleSetData,
                    'academic_policy_id' => $academicPolicy->id,
                    'display_order' => $index + 1,
                    'created_by' => $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]);

                if (! $ruleSet->applies_to_all_stages) {
                    $sync = [];
                    foreach (array_values($sourceIds) as $termIndex => $termId) {
                        $sync[(int) $termId] = ['display_order' => $termIndex + 1];
                    }
                    $ruleSet->sourceTerms()->sync($sync);
                }
            }

            $this->policyService->clearValidationCheckpoint($academicPolicy);

            DB::table('audit_logs')->insert([
                'event' => 'ACADEMIC_POLICY_PROGRESSION_RULE_SETS_UPDATED',
                'resource_type' => 'academic_policy',
                'resource_id' => $academicPolicy->id,
                'before' => json_encode($before),
                'after' => json_encode(
                    $academicPolicy->progressionRuleSets()
                        ->with('sourceTerms:id,curriculum_id,sequence_no,name')
                        ->get()
                        ->toArray()
                ),
                'actor_user_id' => $request->user()->id,
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Promotion / Progression Rule Sets saved successfully.');
    }

    private function availableCurricula(AcademicPolicy $policy): array
    {
        $existingCurriculumIds = $policy->progressionRuleSets
            ->pluck('curriculum_id')
            ->filter()
            ->map(fn ($id) => (int) $id);

        if ($policy->scope_type === 'CURRICULUM' && $policy->curriculum_id) {
            $existingCurriculumIds->push((int) $policy->curriculum_id);
        }

        $existingCurriculumIds = $existingCurriculumIds->unique()->values();

        $query = Curriculum::query()
            ->where('university_id', $policy->university_id)
            ->where('academic_session_id', $policy->academic_session_id)
            ->where(function (Builder $query) use ($existingCurriculumIds, $policy) {
                $query->where(function (Builder $current) use ($policy) {
                    $current->where('lifecycle_status', 'ACTIVE')
                        ->where('approval_status', 'APPROVED')
                        ->whereDoesntHave('amendments', fn (Builder $amendment) =>
                            $amendment->where('approval_status', 'APPROVED')
                        );

                    if ($policy->scope_type === 'CURRICULUM') {
                        $current->whereKey($policy->curriculum_id);
                    } elseif ($policy->scope_type === 'PROGRAM_TEMPLATE') {
                        $current->where('program_template_id', $policy->program_template_id);
                    }
                });

                if ($existingCurriculumIds->isNotEmpty()) {
                    $query->orWhereIn('id', $existingCurriculumIds);
                }
            })
            ->orderBy('name')
            ->orderByDesc('version')
            ->get(['id', 'name', 'code', 'version', 'program_template_id', 'academic_session_id']);

        $terms = DB::table('curriculum_terms')
            ->whereIn('curriculum_id', $query->pluck('id'))
            ->where('status', 'ACTIVE')
            ->orderBy('curriculum_id')
            ->orderBy('sequence_no')
            ->get(['id', 'curriculum_id', 'sequence_no', 'name'])
            ->groupBy('curriculum_id');

        return $query->map(fn (Curriculum $curriculum) => [
            'id' => $curriculum->id,
            'name' => $curriculum->name,
            'code' => $curriculum->code,
            'version' => $curriculum->version,
            'program_template_id' => $curriculum->program_template_id,
            'academic_session_id' => $curriculum->academic_session_id,
            'terms' => collect($terms->get($curriculum->id, []))->values()->all(),
        ])->values()->all();
    }
}
