<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\CollegeAdmissionApplicationChoice;
use App\Models\CollegeAdmissionSelectionRule;
use App\Services\CollegeAdmissionMeritService;
use App\Services\CollegeReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollegeAdmissionMeritController extends Controller
{
    public function index(
        Request $request,
        College $college,
        CollegeAdmissionMeritService $service,
        CollegeReservationService $reservationService
    ): Response {
        $this->authorizeCollege($request, $college, 'college_admission_merit.view');

        $ruleIds = CollegeAdmissionApplicationChoice::query()
            ->where('eligibility_status', 'ELIGIBLE')
            ->whereNotNull('college_admission_selection_rule_id')
            ->whereHas('application', fn ($q) => $q->where('college_id', $college->id)->where('status', 'SUBMITTED'))
            ->distinct()
            ->pluck('college_admission_selection_rule_id');

        $rules = CollegeAdmissionSelectionRule::query()
            ->whereIn('id', $ruleIds)
            ->with([
                'intake.offering.programTemplate.degree.degreeLevel:id,name,code',
                'intake.offering.academicSession:id,name,code,is_current',
                'tieBreakers',
            ])
            ->orderByDesc('id')
            ->get()
            ->map(function (CollegeAdmissionSelectionRule $rule) use ($college, $service, $reservationService) {
                $summary = $service->summary($college, $rule);
                $bucket = $reservationService->availableBuckets($rule->intake)->firstWhere('bucket_key', $rule->bucket_key);

                $program = $rule->intake?->offering?->programTemplate;
                $degree = $program?->degree;
                $degreeLevel = $degree?->degreeLevel;

                return [
                    'id' => $rule->id,
                    'name' => $rule->name,
                    'code' => $rule->code,
                    'version_no' => $rule->version_no,
                    'status' => $rule->status,
                    'selection_mode' => $rule->selection_mode,
                    'bucket_key' => $rule->bucket_key,
                    'bucket_label' => $bucket['label'] ?? $rule->bucket_key,
                    'basis_capacity' => $rule->basis_capacity,
                    'merit_weight_percent' => $rule->merit_weight_percent,
                    'entrance_weight_percent' => $rule->entrance_weight_percent,
                    'interview_weight_percent' => $rule->interview_weight_percent,
                    'minimum_merit_score' => $rule->minimum_merit_score,
                    'minimum_entrance_score' => $rule->minimum_entrance_score,
                    'minimum_interview_score' => $rule->minimum_interview_score,
                    'minimum_final_score' => $rule->minimum_final_score,
                    'program_name' => $program?->name,
                    'program_code' => $program?->code,
                    'degree_level_name' => $degreeLevel?->name,
                    'degree_level_code' => $degreeLevel?->code,
                    'degree_name' => $degree?->name,
                    'degree_code' => $degree?->code,
                    'session_name' => $rule->intake?->offering?->academicSession?->name,
                    'session_code' => $rule->intake?->offering?->academicSession?->code,
                    'tie_breakers' => $rule->tieBreakers->map(fn ($tie) => [
                        'priority' => $tie->priority,
                        'criterion' => $tie->criterion,
                        'comparison_direction' => $tie->comparison_direction,
                        'criterion_reference' => $tie->criterion_reference,
                    ])->values(),
                    'summary' => $summary,
                ];
            })
            ->values();

        $selectedRuleId = (int) $request->query('rule_id', $rules->first()['id'] ?? 0);
        $selectedRule = $selectedRuleId > 0
            ? CollegeAdmissionSelectionRule::query()->with(['intake.offering', 'tieBreakers'])->find($selectedRuleId)
            : null;

        if ($selectedRule) {
            $belongsToVisibleSet = $rules->contains(fn ($row) => (int) $row['id'] === (int) $selectedRule->id);
            if (! $belongsToVisibleSet) {
                $selectedRule = null;
            }
        }

        $preview = $selectedRule ? $service->preview($college, $selectedRule, 100) : null;

        return Inertia::render('college-admission-merit/index', [
            'college' => $college->only(['id', 'name', 'code', 'status']),
            'rules' => $rules,
            'selectedRuleId' => $selectedRule?->id,
            'preview' => $preview,
            'can' => [
                'generate' => $request->user()->hasCollegePermission('college_admission_merit.generate', $college->id),
            ],
        ]);
    }

    public function generate(
        Request $request,
        College $college,
        CollegeAdmissionSelectionRule $rule,
        CollegeAdmissionMeritService $service
    ): RedirectResponse {
        $this->authorizeCollege($request, $college, 'college_admission_merit.generate');
        $entries = $service->generate($college, $rule, $request->user()->id, $request->ip());

        return redirect()
            ->route('college-admission-merit.index', ['college' => $college->id, 'rule_id' => $rule->id])
            ->with('toast', [
                'type' => 'success',
                'message' => $entries->count().' candidate(s) ranked. Merit / Roster is now locked to Selection Rule '.$rule->code.' V'.$rule->version_no.'.',
            ]);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
