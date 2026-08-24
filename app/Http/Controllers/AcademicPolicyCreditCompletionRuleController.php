<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertAcademicPolicyCreditCompletionRuleRequest;
use App\Models\AcademicPolicy;
use App\Models\AcademicPolicyCreditCategoryRequirement;
use App\Models\AcademicPolicyCreditCompletionRule;
use App\Services\AcademicPolicyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcademicPolicyCreditCompletionRuleController extends Controller
{
    public function __construct(private readonly AcademicPolicyService $policyService) {}

    public function edit(Request $request, AcademicPolicy $academicPolicy): Response
    {
        abort_unless($request->user()->hasPermission('academic_policy.view'), 403);

        $academicPolicy->load([
            'academicSession:id,name,code',
            'programTemplate:id,name,code',
            'curriculum:id,name,code,version',
            'creditCompletionRule',
            'creditCategoryRequirements.courseCategory:id,name,code,category_group',
        ]);

        return Inertia::render('admin/academic-policies/credit-completion', [
            'policy' => $academicPolicy,
            'rule' => $academicPolicy->creditCompletionRule,
            'categoryRequirements' => $academicPolicy->creditCategoryRequirements->values(),
            'courseCategories' => DB::table('course_categories')
                ->where('university_id', $academicPolicy->university_id)
                ->where('status', 'ACTIVE')
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(['id', 'name', 'code', 'category_group']),
            'editable' => $request->user()->hasPermission('academic_policy.update') &&
                $academicPolicy->lifecycle_status === 'DRAFT' &&
                ! in_array(
                    $academicPolicy->approval_status ?? 'NOT_SUBMITTED',
                    ['SUBMITTED', 'UNDER_APPROVAL', 'APPROVED'],
                    true
                ),
        ]);
    }

    public function update(
        UpsertAcademicPolicyCreditCompletionRuleRequest $request,
        AcademicPolicy $academicPolicy
    ): RedirectResponse {
        $this->policyService->assertEditable($academicPolicy);
        $data = $request->validated();
        $requirements = collect($data['category_requirements'] ?? [])->values();
        unset($data['category_requirements']);

        if (! $data['allow_credit_transfer']) {
            $data['maximum_credit_transfer_percent'] = null;
        } elseif ($data['maximum_credit_transfer_percent'] === null) {
            throw ValidationException::withMessages([
                'maximum_credit_transfer_percent' =>
                    'Maximum Credit Transfer Percent is required when Credit Transfer is allowed.',
            ]);
        }

        foreach ($requirements as $index => $requirement) {
            $minimum = (float) $requirement['minimum_credits'];
            $maximum = $requirement['maximum_credits'] === null || $requirement['maximum_credits'] === ''
                ? null
                : (float) $requirement['maximum_credits'];

            if ($maximum !== null && $maximum < $minimum) {
                throw ValidationException::withMessages([
                    "category_requirements.{$index}.maximum_credits" =>
                        'Maximum Credits cannot be less than Minimum Credits.',
                ]);
            }
        }

        DB::transaction(function () use ($academicPolicy, $data, $requirements, $request) {
            $existingRule = AcademicPolicyCreditCompletionRule::query()
                ->where('academic_policy_id', $academicPolicy->id)
                ->first();

            $before = [
                'rule' => $existingRule?->toArray(),
                'category_requirements' => AcademicPolicyCreditCategoryRequirement::query()
                    ->where('academic_policy_id', $academicPolicy->id)
                    ->orderBy('display_order')
                    ->orderBy('id')
                    ->get()
                    ->toArray(),
            ];

            $rule = AcademicPolicyCreditCompletionRule::query()->updateOrCreate(
                ['academic_policy_id' => $academicPolicy->id],
                [
                    ...$data,
                    'created_by' => $existingRule?->created_by ?? $request->user()->id,
                    'updated_by' => $request->user()->id,
                ]
            );

            $submittedCategoryIds = $requirements
                ->pluck('course_category_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            AcademicPolicyCreditCategoryRequirement::query()
                ->where('academic_policy_id', $academicPolicy->id)
                ->when(
                    $submittedCategoryIds !== [],
                    fn ($query) => $query->whereNotIn('course_category_id', $submittedCategoryIds)
                )
                ->when(
                    $submittedCategoryIds === [],
                    fn ($query) => $query
                )
                ->delete();

            foreach ($requirements as $index => $requirement) {
                $existing = AcademicPolicyCreditCategoryRequirement::query()
                    ->where('academic_policy_id', $academicPolicy->id)
                    ->where('course_category_id', (int) $requirement['course_category_id'])
                    ->first();

                AcademicPolicyCreditCategoryRequirement::query()->updateOrCreate(
                    [
                        'academic_policy_id' => $academicPolicy->id,
                        'course_category_id' => (int) $requirement['course_category_id'],
                    ],
                    [
                        'minimum_credits' => number_format((float) $requirement['minimum_credits'], 2, '.', ''),
                        'maximum_credits' => $requirement['maximum_credits'] === null || $requirement['maximum_credits'] === ''
                            ? null
                            : number_format((float) $requirement['maximum_credits'], 2, '.', ''),
                        'display_order' => (int) ($requirement['display_order'] ?? ($index + 1)),
                        'created_by' => $existing?->created_by ?? $request->user()->id,
                        'updated_by' => $request->user()->id,
                    ]
                );
            }

            $this->policyService->clearValidationCheckpoint($academicPolicy);

            $after = [
                'rule' => $rule->fresh()->toArray(),
                'category_requirements' => AcademicPolicyCreditCategoryRequirement::query()
                    ->where('academic_policy_id', $academicPolicy->id)
                    ->orderBy('display_order')
                    ->orderBy('id')
                    ->get()
                    ->toArray(),
            ];

            DB::table('audit_logs')->insert([
                'event' => $existingRule
                    ? 'ACADEMIC_POLICY_CREDIT_COMPLETION_UPDATED'
                    : 'ACADEMIC_POLICY_CREDIT_COMPLETION_CREATED',
                'resource_type' => 'academic_policy_credit_completion_rule',
                'resource_id' => $rule->id,
                'before' => json_encode($before),
                'after' => json_encode($after),
                'actor_user_id' => $request->user()->id,
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'Credit / Completion Policy saved successfully.');
    }
}
