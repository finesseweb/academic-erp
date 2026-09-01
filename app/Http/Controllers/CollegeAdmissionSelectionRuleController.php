<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollegeAdmissionSelectionRuleRequest;
use App\Http\Requests\UpdateCollegeAdmissionSelectionRuleRequest;
use App\Models\College;
use App\Models\CollegeAdmissionSelectionRule;
use App\Models\CollegeAdmissionFormField;
use App\Models\CollegeAdmissionFormMapping;
use App\Models\CollegeProgramIntake;
use App\Models\CollegeProgramReservationPlan;
use App\Services\CollegeAdmissionSelectionRuleService;
use App\Services\CollegeReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollegeAdmissionSelectionRuleController extends Controller
{
    public function index(Request $request, College $college, CollegeReservationService $reservationService): Response
    {
        $this->authorizeCollege($request, $college, 'college_admission_selection_rule.view');

        $intakes = CollegeProgramIntake::query()
            ->with([
                'offering.programTemplate:id,name,code',
                'offering.academicSession:id,name,code,is_current',
                'allocations.discipline:id,name,code',
                'allocations.specialization:id,name,code',
            ])
            ->where('status', 'ACTIVE')
            ->whereHas('offering', fn ($q) => $q->where('college_id', $college->id)->where('status', 'ACTIVE'))
            ->get();

        $plans = CollegeProgramReservationPlan::query()
            ->whereIn('college_program_intake_id', $intakes->pluck('id'))
            ->get()
            ->keyBy(fn ($plan) => $plan->college_program_intake_id.'|'.$plan->bucket_key);

        $allBuckets = $intakes->flatMap(function ($intake) use ($reservationService, $plans) {
            return $reservationService->availableBuckets($intake)->map(function ($bucket) use ($intake, $plans) {
                $plan = $plans->get($intake->id.'|'.$bucket['bucket_key']);
                $reservationState = ! $plan ? 'NOT_DEFINED' : $plan->status;

                return [
                    'college_program_intake_id' => $intake->id,
                    'college_program_offering_id' => $intake->offering->id,
                    'bucket_key' => $bucket['bucket_key'],
                    'bucket_type' => $bucket['bucket_type'],
                    'bucket_label' => $bucket['label'],
                    'basis_capacity' => (int) $bucket['basis_capacity'],
                    'program_name' => $intake->offering->programTemplate->name,
                    'program_code' => $intake->offering->programTemplate->code,
                    'session_name' => $intake->offering->academicSession->name,
                    'session_code' => $intake->offering->academicSession->code,
                    'is_current_session' => (bool) $intake->offering->academicSession->is_current,
                    'reservation_plan_id' => $plan?->id,
                    'reservation_state' => $reservationState,
                    'eligible' => ! $plan || $plan->status === 'ACTIVE',
                ];
            });
        })->sortBy([
            fn ($a, $b) => (int) $b['is_current_session'] <=> (int) $a['is_current_session'],
            fn ($a, $b) => strcmp($a['program_name'], $b['program_name']),
            fn ($a, $b) => strcmp($a['bucket_label'], $b['bucket_label']),
        ])->values();

        $eligibleBuckets = $allBuckets->where('eligible', true)->values();
        $blockedBuckets = $allBuckets->where('eligible', false)->values();

        $rules = CollegeAdmissionSelectionRule::query()
            ->with([
                'intake.offering.programTemplate:id,name,code',
                'intake.offering.academicSession:id,name,code,is_current',
                'reservationPlan:id,status',
                'meritSources.obtainedField:id,label,field_key,field_type',
                'meritSources.maximumField:id,label,field_key,field_type',
                'tieBreakers',
            ])
            ->whereHas('intake.offering', fn ($q) => $q->where('college_id', $college->id))
            ->orderByDesc('id')
            ->get()
            ->map(function ($rule) use ($reservationService) {
                $bucket = $reservationService->availableBuckets($rule->intake)->firstWhere('bucket_key', $rule->bucket_key);
                $rule->setAttribute('bucket_label', $bucket['label'] ?? $rule->bucket_key);
                $rule->setAttribute('reservation_state', $rule->reservationPlan?->status ?? 'NOT_DEFINED');
                return $rule;
            });

        $offeringIds = $eligibleBuckets->pluck('college_program_offering_id')->unique()->values();
        $mappings = CollegeAdmissionFormMapping::query()
            ->where('college_id', $college->id)
            ->where('status', 'ACTIVE')
            ->whereIn('college_program_offering_id', $offeringIds)
            ->get(['college_program_offering_id','college_admission_form_template_id']);
        $templatesByOffering = $mappings->groupBy('college_program_offering_id');
        $templateIds = $mappings->pluck('college_admission_form_template_id')->unique();
        $numericFields = CollegeAdmissionFormField::query()
            ->with('step:id,college_admission_form_template_id')
            ->where('field_type', 'NUMBER')->where('status','ACTIVE')
            ->whereHas('step', fn($q) => $q->whereIn('college_admission_form_template_id', $templateIds))
            ->orderBy('display_order')->orderBy('id')->get(['id','college_admission_form_step_id','field_key','label','field_type']);
        $scoreSourceFields = $offeringIds->flatMap(function ($offeringId) use ($templatesByOffering, $numericFields) {
            $templateIds = $templatesByOffering->get($offeringId, collect())->pluck('college_admission_form_template_id')->map(fn($id)=>(int)$id)->all();
            return $numericFields->filter(fn($field) => in_array((int)$field->step?->college_admission_form_template_id, $templateIds, true))->map(fn($field) => [
                'college_program_offering_id'=>(int)$offeringId,'field_id'=>(int)$field->id,'label'=>$field->label,'field_key'=>$field->field_key,
            ]);
        })->values();

        return Inertia::render('college-admission-selection-rules/index', [
            'college' => $college->only(['id','name','code','status']),
            'eligibleBuckets' => $eligibleBuckets,
            'blockedBuckets' => $blockedBuckets,
            'rules' => $rules,
            'scoreSourceFields' => $scoreSourceFields,
            'can' => [
                'create' => $request->user()->hasCollegePermission('college_admission_selection_rule.create', $college->id),
                'update' => $request->user()->hasCollegePermission('college_admission_selection_rule.update', $college->id),
                'enable' => $request->user()->hasCollegePermission('college_admission_selection_rule.enable', $college->id),
                'disable' => $request->user()->hasCollegePermission('college_admission_selection_rule.disable', $college->id),
            ],
        ]);
    }

    public function store(StoreCollegeAdmissionSelectionRuleRequest $request, College $college, CollegeAdmissionSelectionRuleService $service): RedirectResponse
    {
        $service->create($college, $request->validated(), $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => 'Selection Rule version created as INACTIVE.']);
    }

    public function update(UpdateCollegeAdmissionSelectionRuleRequest $request, College $college, CollegeAdmissionSelectionRule $rule, CollegeAdmissionSelectionRuleService $service): RedirectResponse
    {
        $service->update($rule, $college, $request->validated(), $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => 'Selection Rule updated.']);
    }

    public function activate(Request $request, College $college, CollegeAdmissionSelectionRule $rule, CollegeAdmissionSelectionRuleService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_admission_selection_rule.enable');
        $service->activate($rule, $college, $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => 'Selection Rule activated. Any previous active version for this seat bucket was retired.']);
    }

    public function retire(Request $request, College $college, CollegeAdmissionSelectionRule $rule, CollegeAdmissionSelectionRuleService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_admission_selection_rule.disable');
        $service->retire($rule, $college, $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => 'Selection Rule retired.']);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
