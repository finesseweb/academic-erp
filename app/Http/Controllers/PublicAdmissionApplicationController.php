<?php

namespace App\Http\Controllers;

use App\Models\CollegeAdmissionFormMapping;
use App\Models\CollegeAdmissionSelectionRule;
use App\Models\CollegeProgramIntake;
use App\Models\CollegeProgramReservationPlan;
use App\Services\CollegeAdmissionApplicationService;
use App\Services\CollegeAdmissionFormResolver;
use App\Services\CollegeReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PublicAdmissionApplicationController extends Controller
{
    public function show(
        Request $request,
        string $slug,
        CollegeReservationService $reservationService,
        CollegeAdmissionFormResolver $formResolver,
    ): Response {
        $mapping = $this->publicMapping($slug);
        $mapping->loadMissing([
            'template.parent.steps.panels',
            'template.parent.steps.fields.options',
            'template.parent.steps.fields.conditions',
            'template.parent.steps.fields.scopes',
            'template.steps.panels',
            'template.steps.fields.options',
            'template.steps.fields.conditions',
            'template.steps.fields.scopes',
        ]);

        $college = DB::table('colleges')->where('id', $mapping->college_id)->first();
        abort_unless($college && $college->status === 'ACTIVE', 404);

        $cycle = \App\Models\CollegeAdmissionCycle::query()
            ->with(['programOffering.programTemplate:id,name,code', 'academicSession:id,name,code'])
            ->whereKey($mapping->college_admission_cycle_id)
            ->where('college_id', $mapping->college_id)
            ->where('college_program_offering_id', $mapping->college_program_offering_id)
            ->firstOrFail();

        $availability = $this->availability($mapping, $cycle);
        $contexts = $availability['can_submit']
            ? $this->selectionContexts($mapping, $cycle, $reservationService)
            : collect();
        $fee = $formResolver->resolveFee(\App\Models\College::query()->findOrFail($mapping->college_id), $cycle);

        return Inertia::render('public/admission-application', [
            'publicForm' => [
                'slug' => $mapping->public_slug,
                'step_display_mode' => ($mapping->public_open_mode ?? 'SAME_WINDOW'),
                'college' => ['id'=>$college->id,'name'=>$college->name,'code'=>$college->code],
                'cycle' => [
                    'id'=>$cycle->id,'name'=>$cycle->name,'code'=>$cycle->code,
                    'application_start_date'=>$cycle->application_start_date?->toDateString(),
                    'application_end_date'=>$cycle->application_end_date?->toDateString(),
                    'status'=>$cycle->status,
                ],
                'program' => [
                    'name'=>$cycle->programOffering?->programTemplate?->name,
                    'code'=>$cycle->programOffering?->programTemplate?->code,
                    'session'=>$cycle->academicSession?->name,
                ],
                'template' => $formResolver->templatePayload($mapping->template, $cycle),
                'fee' => ['required'=>$fee['required'],'amount'=>$fee['amount'],'currency'=>$fee['currency']],
                'choices' => $contexts->values(),
                'availability' => $availability,
            ],
            'successApplicationNo' => $request->session()->pull('public_application_success'),
        ]);
    }

    public function store(
        Request $request,
        string $slug,
        CollegeReservationService $reservationService,
        CollegeAdmissionApplicationService $applicationService,
    ): RedirectResponse {
        $mapping = $this->publicMapping($slug);
        $college = \App\Models\College::query()->whereKey($mapping->college_id)->where('status','ACTIVE')->firstOrFail();
        $cycle = \App\Models\CollegeAdmissionCycle::query()
            ->whereKey($mapping->college_admission_cycle_id)
            ->where('college_id', $college->id)
            ->where('college_program_offering_id', $mapping->college_program_offering_id)
            ->firstOrFail();

        $availability = $this->availability($mapping, $cycle);
        if (! $availability['can_submit']) {
            throw ValidationException::withMessages(['application' => $availability['message']]);
        }

        $validContexts = $this->selectionContexts($mapping, $cycle, $reservationService);
        if ($validContexts->isEmpty()) {
            throw ValidationException::withMessages(['choices' => 'No public Regular Admission seat category is currently available for this Program Offering.']);
        }

        $data = $request->validate([
            'candidate_name' => ['required','string','max:180'],
            'email' => ['nullable','email:rfc','max:190'],
            'phone' => ['nullable','string','max:40'],
            'date_of_birth' => ['required','date','before_or_equal:today'],
            'external_reference' => ['nullable','string','max:120'],
            'remarks' => ['nullable','string','max:3000'],
            'custom_fields' => ['nullable','array'],
            'choices' => ['required','array','min:1','max:1'],
            'choices.0.college_program_intake_id' => ['required','integer'],
            'choices.0.bucket_key' => ['required','string','max:80'],
        ]);

        $requestedChoice = $data['choices'][0];
        $allowed = $validContexts->contains(fn ($context) =>
            (int) $context['college_program_intake_id'] === (int) $requestedChoice['college_program_intake_id']
            && (string) $context['bucket_key'] === (string) $requestedChoice['bucket_key']
        );
        if (! $allowed) {
            throw ValidationException::withMessages(['choices' => 'Select a currently available admission seat category.']);
        }

        $payload = [
            ...$data,
            'college_admission_cycle_id' => $cycle->id,
            'admission_mode' => 'REGULAR',
        ];

        $application = DB::transaction(function () use ($applicationService, $college, $payload, $request) {
            $application = $applicationService->create($college, $payload, null, $request->ip(), 'PUBLIC');
            return $applicationService->submit($application, $college, null, $request->ip());
        });

        return redirect()->route('public-admission.show', ['slug'=>$slug])
            ->with('public_application_success', $application->application_no);
    }

    private function publicMapping(string $slug): CollegeAdmissionFormMapping
    {
        return CollegeAdmissionFormMapping::query()
            ->with('template')
            ->where('public_slug', $slug)
            ->where('public_enabled', true)
            ->where('status', 'ACTIVE')
            ->whereNotNull('college_id')
            ->whereNotNull('college_program_offering_id')
            ->whereNotNull('college_admission_cycle_id')
            ->whereHas('template', fn ($q) => $q->where('status','ACTIVE')->whereIn('admission_mode',['REGULAR','BOTH']))
            ->firstOrFail();
    }

    private function availability(CollegeAdmissionFormMapping $mapping, \App\Models\CollegeAdmissionCycle $cycle): array
    {
        if ($cycle->status !== 'ACTIVE') {
            return ['can_submit'=>false,'state'=>'CLOSED','message'=>'Applications are not currently active for this admission cycle.'];
        }
        if (! $cycle->programOffering || $cycle->programOffering->status !== 'ACTIVE') {
            return ['can_submit'=>false,'state'=>'UNAVAILABLE','message'=>'This Program Offering is not currently available for applications.'];
        }

        $today = today()->toDateString();
        $start = $cycle->application_start_date?->toDateString();
        $end = $cycle->application_end_date?->toDateString();
        if ($start && $today < $start) {
            return ['can_submit'=>false,'state'=>'UPCOMING','message'=>'Applications have not opened yet.','opens_on'=>$start,'closes_on'=>$end];
        }
        if ($end && $today > $end) {
            return ['can_submit'=>false,'state'=>'CLOSED','message'=>'The application window has closed.','opens_on'=>$start,'closes_on'=>$end];
        }
        return ['can_submit'=>true,'state'=>'OPEN','message'=>'Applications are open.','opens_on'=>$start,'closes_on'=>$end];
    }

    private function selectionContexts(
        CollegeAdmissionFormMapping $mapping,
        \App\Models\CollegeAdmissionCycle $cycle,
        CollegeReservationService $reservationService,
    ) {
        return CollegeProgramIntake::query()
            ->with(['allocations.discipline:id,name,code','allocations.specialization:id,name,code'])
            ->where('college_program_offering_id', $mapping->college_program_offering_id)
            ->where('status','ACTIVE')
            ->get()
            ->flatMap(function (CollegeProgramIntake $intake) use ($reservationService) {
                return $reservationService->availableBuckets($intake)->map(function ($bucket) use ($intake) {
                    $plan = CollegeProgramReservationPlan::query()
                        ->where('college_program_intake_id',$intake->id)
                        ->where('bucket_key',$bucket['bucket_key'])
                        ->first();
                    if ($plan && $plan->status !== 'ACTIVE') return null;

                    $rule = CollegeAdmissionSelectionRule::query()
                        ->where('college_program_intake_id',$intake->id)
                        ->where('bucket_key',$bucket['bucket_key'])
                        ->where('status','ACTIVE')
                        ->orderByDesc('version_no')
                        ->first();
                    if (! $rule) return null;
                    if ((int) ($rule->college_program_reservation_plan_id ?? 0) !== (int) ($plan?->id ?? 0)) return null;

                    return [
                        'college_program_intake_id'=>$intake->id,
                        'bucket_key'=>$bucket['bucket_key'],
                        'label'=>$bucket['label'],
                        'basis_capacity'=>(int)$bucket['basis_capacity'],
                    ];
                })->filter();
            })->values();
    }
}
