<?php

namespace App\Http\Controllers;

use App\Models\CollegeAdmissionFormMapping;
use App\Models\CollegeAdmissionSelectionRule;
use App\Models\CollegeProgramIntake;
use App\Models\CollegeProgramReservationPlan;
use App\Services\CollegeAdmissionApplicationService;
use App\Services\ApplicantAcademicPreferenceService;
use App\Services\ApplicantRegistrationNumberService;
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
        ApplicantAcademicPreferenceService $academicPreferenceService,
        ApplicantRegistrationNumberService $registrationNumbers,
    ): Response|RedirectResponse {
        if (! $request->user() || $request->user()->account_type !== 'APPLICANT') return redirect()->route('applicant.gateway', ['slug'=>$slug]);
        $mapping = $this->publicMapping($slug);
        abort_unless((int)$request->user()->primary_college_id === (int)$mapping->college_id, 403);
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
        // Public applications are intentionally NOT seat-capacity gated.
        // Discipline / specialization / curriculum choices are collected now; seat allocation is downstream.
        $contexts = collect();
        $academicOptions = $academicPreferenceService->options($cycle);
        $fee = $formResolver->resolveFee(\App\Models\College::query()->findOrFail($mapping->college_id), $cycle);

        $applicantProfile = \App\Models\ApplicantProfile::where('user_id',$request->user()->id)->firstOrFail();
        $registrationNo = $registrationNumbers->ensure($applicantProfile);
        $portalSettings = \App\Models\CollegeApplicantRegistrationSetting::forCollege($mapping->college_id);

        return Inertia::render('public/admission-application', [
            'publicForm' => [
                'slug' => $mapping->public_slug,
                'step_display_mode' => ($mapping->public_open_mode ?? 'SAME_WINDOW'),
                'seat_selection_required' => false,
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
                'choices' => [],
                'academic_options' => $academicOptions,
                'availability' => $availability,
                'help_text' => $portalSettings->application_help_text,
            ],
            'applicant' => ['name'=>$request->user()->name,'email'=>$request->user()->email,'phone'=>$request->user()->mobile,'date_of_birth'=>$applicantProfile->date_of_birth?->toDateString(),'registration_no'=>$registrationNo],
            'successApplicationNo' => $request->session()->pull('public_application_success'),
        ]);
    }

    public function store(
        Request $request,
        string $slug,
        CollegeReservationService $reservationService,
        CollegeAdmissionApplicationService $applicationService,
        ApplicantAcademicPreferenceService $academicPreferenceService,
    ): RedirectResponse {
        abort_unless($request->user() && $request->user()->account_type === 'APPLICANT', 403);
        $mapping = $this->publicMapping($slug);
        abort_unless((int)$request->user()->primary_college_id === (int)$mapping->college_id, 403);
        $settings = \App\Models\CollegeApplicantRegistrationSetting::forCollege($mapping->college_id);
        if ($settings->email_verification_required && ! $request->user()->email_verified_at) return redirect()->route('applicant.verify.notice',['slug'=>$slug]);
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

        $data = $request->validate([
            'external_reference' => ['nullable','string','max:120'],
            'remarks' => ['nullable','string','max:3000'],
            'custom_fields' => ['nullable','array'],
            'academic_preference' => ['required','array'],
            'academic_preference.discipline_id' => ['nullable','integer'],
            'academic_preference.specialization_id' => ['nullable','integer'],
            'academic_preference.course_choices' => ['nullable','array'],
            'academic_preference.course_choices.*' => ['nullable','array'],
            'academic_preference.course_choices.*.*' => ['integer'],
            'preview_confirmed' => ['accepted'],
        ]);
        // Public application intake is never limited by seat capacity. Seat/reservation/allocation happens later.
        $data['choices'] = [];
        $resolvedAcademicPreference = $academicPreferenceService->resolve($cycle, $data['academic_preference'] ?? []);

        $profile = \App\Models\ApplicantProfile::query()->where('user_id',$request->user()->id)->where('college_id',$mapping->college_id)->firstOrFail();
        $data['candidate_name'] = $request->user()->name;
        $data['email'] = $request->user()->email;
        $data['phone'] = $profile->phone ?: $request->user()->mobile;
        $data['date_of_birth'] = $profile->date_of_birth->toDateString();
        $data['applicant_user_id'] = $request->user()->id;

        $payload = [
            ...$data,
            'college_admission_cycle_id' => $cycle->id,
            'admission_mode' => 'REGULAR',
        ];

        $application = DB::transaction(function () use ($applicationService, $academicPreferenceService, $resolvedAcademicPreference, $college, $payload, $request) {
            $application = $applicationService->create($college, $payload, null, $request->ip(), 'PUBLIC');
            $academicPreferenceService->persist($application, $resolvedAcademicPreference);
            return $applicationService->submit($application, $college, null, $request->ip());
        });

        return redirect()->route('applicant.application', ['slug'=>$slug])
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
