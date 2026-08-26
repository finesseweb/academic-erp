<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollegeAdmissionApplicationRequest;
use App\Http\Requests\UpdateCollegeAdmissionApplicationRequest;
use App\Models\College;
use App\Models\CollegeAdmissionApplication;
use App\Models\CollegeAdmissionApplicationChoice;
use App\Models\CollegeAdmissionCycle;
use App\Models\CollegeAdmissionSelectionRule;
use App\Services\CollegeAdmissionApplicationService;
use App\Services\CollegeReservationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeAdmissionApplicationController extends Controller
{
    public function index(Request $request, College $college, CollegeReservationService $reservationService): Response
    {
        $this->authorizeCollege($request, $college, 'college_admission_application.view');

        $cycles = CollegeAdmissionCycle::query()
            ->with(['academicSession:id,name,code,is_current','programOffering.programTemplate:id,name,code','programOffering.academicSession:id,name,code,is_current'])
            ->where('college_id', $college->id)
            ->orderByRaw("FIELD(status, 'ACTIVE', 'INACTIVE', 'CLOSED')")
            ->orderByDesc('id')
            ->get();

        $rules = CollegeAdmissionSelectionRule::query()
            ->with([
                'intake.allocations.discipline:id,name,code',
                'intake.allocations.specialization:id,name,code',
                'intake.offering.programTemplate:id,name,code',
                'intake.offering.academicSession:id,name,code,is_current',
                'reservationPlan:id,status',
            ])
            ->where('status', 'ACTIVE')
            ->whereHas('intake', fn ($q) => $q->where('status', 'ACTIVE'))
            ->whereHas('intake.offering', fn ($q) => $q->where('college_id', $college->id)->where('status', 'ACTIVE'))
            ->get();

        $selectionContexts = $rules->map(function (CollegeAdmissionSelectionRule $rule) use ($reservationService) {
            if ($rule->reservationPlan && $rule->reservationPlan->status !== 'ACTIVE') {
                return null;
            }
            $bucket = $reservationService->availableBuckets($rule->intake)->firstWhere('bucket_key', $rule->bucket_key);
            if (! $bucket) {
                return null;
            }
            $offering = $rule->intake->offering;
            return [
                'college_admission_selection_rule_id' => $rule->id,
                'college_program_intake_id' => $rule->college_program_intake_id,
                'college_program_offering_id' => $offering->id,
                'academic_session_id' => $offering->academic_session_id,
                'program_name' => $offering->programTemplate->name,
                'program_code' => $offering->programTemplate->code,
                'session_name' => $offering->academicSession->name,
                'session_code' => $offering->academicSession->code,
                'is_current_session' => (bool) $offering->academicSession->is_current,
                'bucket_key' => $rule->bucket_key,
                'bucket_type' => $rule->bucket_type,
                'bucket_label' => $bucket['label'],
                'basis_capacity' => (int) $bucket['basis_capacity'],
                'reservation_plan_id' => $rule->college_program_reservation_plan_id,
                'reservation_state' => $rule->reservationPlan ? 'ACTIVE' : 'NOT_DEFINED',
                'rule_name' => $rule->name,
                'rule_code' => $rule->code,
                'rule_version' => $rule->version_no,
                'selection_mode' => $rule->selection_mode,
                'merit_weight_percent' => $rule->merit_weight_percent,
                'entrance_weight_percent' => $rule->entrance_weight_percent,
                'interview_weight_percent' => $rule->interview_weight_percent,
            ];
        })->filter()->sortBy([
            fn ($a, $b) => (int) $b['is_current_session'] <=> (int) $a['is_current_session'],
            fn ($a, $b) => strcmp($a['program_name'], $b['program_name']),
            fn ($a, $b) => strcmp($a['bucket_label'], $b['bucket_label']),
        ])->values();

        $search = trim((string) $request->query('search', ''));
        $applications = CollegeAdmissionApplication::query()
            ->with([
                'admissionCycle.academicSession:id,name,code,is_current',
                'admissionCycle.programOffering.programTemplate:id,name,code',
                'admissionCycle.programOffering.academicSession:id,name,code,is_current',
                'choices.intake.allocations.discipline:id,name,code',
                'choices.intake.allocations.specialization:id,name,code',
                'choices.intake.offering.programTemplate:id,name,code',
                'choices.intake.offering.academicSession:id,name,code,is_current',
                'choices.reservationPlan:id,status',
                'choices.selectionRule:id,name,code,version_no,selection_mode,status,merit_weight_percent,entrance_weight_percent,interview_weight_percent',
            ])
            ->where('college_id', $college->id)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('application_no', 'like', "%{$search}%")
                        ->orWhere('candidate_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('external_reference', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $applications->getCollection()->each(function (CollegeAdmissionApplication $application) use ($reservationService) {
            $application->choices->each(function ($choice) use ($reservationService) {
                $bucket = $reservationService->availableBuckets($choice->intake)->firstWhere('bucket_key', $choice->bucket_key);
                $choice->setAttribute('bucket_label', $bucket['label'] ?? $choice->bucket_key);
            });
        });

        return Inertia::render('college-admission-applications/index', [
            'college' => $college->only(['id', 'name', 'code', 'status']),
            'cycles' => $cycles,
            'selectionContexts' => $selectionContexts,
            'applications' => $applications,
            'filters' => ['search' => $search],
            'can' => [
                'create' => $request->user()->hasCollegePermission('college_admission_application.create', $college->id),
                'update' => $request->user()->hasCollegePermission('college_admission_application.update', $college->id),
                'submit' => $request->user()->hasCollegePermission('college_admission_application.submit', $college->id),
                'eligibility' => $request->user()->hasCollegePermission('college_admission_application.eligibility', $college->id),
                'withdraw' => $request->user()->hasCollegePermission('college_admission_application.withdraw', $college->id),
            ],
        ]);
    }

    public function store(StoreCollegeAdmissionApplicationRequest $request, College $college, CollegeAdmissionApplicationService $service): RedirectResponse
    {
        $service->create($college, $request->validated(), $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => 'Admission Application created as DRAFT.']);
    }

    public function update(UpdateCollegeAdmissionApplicationRequest $request, College $college, CollegeAdmissionApplication $application, CollegeAdmissionApplicationService $service): RedirectResponse
    {
        $service->update($application, $college, $request->validated(), $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => 'Draft Admission Application updated.']);
    }

    public function submit(Request $request, College $college, CollegeAdmissionApplication $application, CollegeAdmissionApplicationService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_admission_application.submit');
        $service->submit($application, $college, $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => 'Application submitted. The exact active Selection Rule version is now locked to each program choice.']);
    }

    public function eligibility(Request $request, College $college, CollegeAdmissionApplicationChoice $choice, CollegeAdmissionApplicationService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_admission_application.eligibility');
        $data = $request->validate([
            'eligibility_status' => ['required', Rule::in(['PENDING', 'ELIGIBLE', 'INELIGIBLE'])],
            'eligibility_reason' => ['nullable', 'string', 'max:3000'],
        ]);
        $service->setEligibility($choice, $college, $data['eligibility_status'], $data['eligibility_reason'] ?? null, $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => 'Candidate eligibility updated for the selected program choice.']);
    }

    public function withdraw(Request $request, College $college, CollegeAdmissionApplication $application, CollegeAdmissionApplicationService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_admission_application.withdraw');
        $service->withdraw($application, $college, $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => 'Admission Application withdrawn.']);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
