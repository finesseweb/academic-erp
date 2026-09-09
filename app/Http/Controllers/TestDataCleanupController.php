<?php

namespace App\Http\Controllers;

use App\Models\AcademicPolicy;
use App\Models\Curriculum;
use App\Models\University;
use App\Services\TestDataCleanupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TestDataCleanupController extends Controller
{
    public function __construct(
        private readonly TestDataCleanupService $service
    ) {}

    public function index(Request $request): Response
    {
        $this->authorizeCleanup($request);

        $university = University::query()->firstOrFail();

        $curricula = Curriculum::query()
            ->with([
                'programTemplate:id,name,code',
                'academicSession:id,name,code',
            ])
            ->where('university_id', $university->id)
            ->orderByDesc('id')
            ->get()
            ->map(function (Curriculum $curriculum) {
                $preview =
                    $this->service
                        ->curriculumCleanupPreview($curriculum);

                return [
                    ...$preview,
                    'program_template_name' =>
                        $curriculum->programTemplate?->name,
                    'academic_session_name' =>
                        $curriculum->academicSession?->name,
                    'can_cleanup' =>
                        count(
                            $preview['downstream_references']
                        ) === 0,
                    'can_reset_approval' =>
                        count(
                            $preview['downstream_references']
                        ) === 0,
                ];
            })
            ->values();

        $academicPolicies = AcademicPolicy::query()
            ->where('university_id', $university->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (AcademicPolicy $policy) =>
                $this->service
                    ->academicPolicyCleanupPreview($policy)
            )
            ->values();

        return Inertia::render(
            'admin/system-maintenance/test-data-cleanup',
            [
                'enabled' =>
                    (bool) config('test-data-cleanup.enabled'),
                'environment' => app()->environment(),
                'curricula' => $curricula,
                'academicPolicies' => $academicPolicies,
                'entities' =>
                    $this->service
                        ->listMaintenanceEntities($university->id),
                'fullReset' =>
                    $this->service
                        ->fullAcademicResetPreview($university->id),
                'accessReset' =>
                    $this->service
                        ->accessResetPreview($university->id, $request->user()->id),
                'legacyUnlinkedRegularApplications' =>
                    $this->service
                        ->legacyUnlinkedRegularApplicationsPreview($university->id),
            ]
        );
    }

    public function resetCurriculumApproval(
        Request $request,
        Curriculum $curriculum
    ): RedirectResponse {
        $this->authorizeCleanup($request);
        $this->confirmCode(
            $request,
            (string) $curriculum->code
        );

        $this->service->resetCurriculumApproval(
            $curriculum,
            $request->user()->id
        );

        return back()->with(
            'success',
            'Test approval state reset. Curriculum is back to DRAFT / NOT_SUBMITTED.'
        );
    }

    public function destroyCurriculum(
        Request $request,
        Curriculum $curriculum
    ): RedirectResponse {
        $this->authorizeCleanup($request);
        $this->confirmCode(
            $request,
            (string) $curriculum->code
        );

        $this->service->cleanupCurriculum(
            $curriculum,
            $request->user()->id
        );

        return back()->with(
            'success',
            'Selected test Curriculum and dependent test structure were cleaned successfully.'
        );
    }

    public function resetAcademicPolicyApproval(
        Request $request,
        AcademicPolicy $academicPolicy
    ): RedirectResponse {
        $this->authorizeCleanup($request);
        $this->confirmCode(
            $request,
            (string) $academicPolicy->code
        );

        $this->service->resetAcademicPolicyApproval(
            $academicPolicy,
            $request->user()->id
        );

        return back()->with(
            'success',
            'Test Academic Policy approval state reset to DRAFT / NOT_SUBMITTED.'
        );
    }

    public function destroyAcademicPolicy(
        Request $request,
        AcademicPolicy $academicPolicy
    ): RedirectResponse {
        $this->authorizeCleanup($request);
        $this->confirmCode(
            $request,
            (string) $academicPolicy->code
        );

        $this->service->cleanupAcademicPolicy(
            $academicPolicy,
            $request->user()->id
        );

        return back()->with(
            'success',
            'Selected Academic Policy test version chain and dependent policy configuration were cleaned successfully.'
        );
    }

    public function fullReset(
        Request $request
    ): RedirectResponse {
        $this->authorizeCleanup($request);

        $university = University::query()->firstOrFail();

        $request->validate([
            'confirmation_code' => [
                'required',
                'string',
                'max:120',
            ],
        ]);

        $expected = 'RESET-ACADEMIC-TEST-DATA';

        if (
            trim((string) $request->input(
                'confirmation_code'
            )) !== $expected
        ) {
            throw ValidationException::withMessages([
                'confirmation_code' =>
                    'Type RESET-ACADEMIC-TEST-DATA exactly to run the full test reset.',
            ]);
        }

        $this->service->fullAcademicReset(
            $university->id,
            $request->user()->id
        );

        return back()->with(
            'success',
            'Full academic test data reset completed in dependency-safe order. System core and access data were preserved.'
        );
    }


    public function fullAccessReset(
        Request $request
    ): RedirectResponse {
        $this->authorizeCleanup($request);

        $university = University::query()->firstOrFail();

        $request->validate([
            'confirmation_code' => ['required', 'string', 'max:120'],
        ]);

        $expected = 'RESET-ACCESS-TEST-DATA';
        if (trim((string) $request->input('confirmation_code')) !== $expected) {
            throw ValidationException::withMessages([
                'confirmation_code' =>
                    'Type RESET-ACCESS-TEST-DATA exactly to clean test Users and custom Roles.',
            ]);
        }

        $result = $this->service->fullAccessReset(
            $university->id,
            $request->user()->id
        );

        return back()->with(
            'success',
            $result['users_deleted'].' user(s) and '.$result['roles_deleted'].' custom role(s) cleaned. Protected/system or operationally referenced records were preserved.'
        );
    }

    public function cleanupLegacyUnlinkedRegularApplications(
        Request $request
    ): RedirectResponse {
        $this->authorizeCleanup($request);

        $university = University::query()->firstOrFail();

        $request->validate([
            'confirmation_code' => ['required', 'string', 'max:120'],
        ]);

        $expected = 'CLEAN-UNLINKED-REGULAR-APPLICATIONS';
        if (trim((string) $request->input('confirmation_code')) !== $expected) {
            throw ValidationException::withMessages([
                'confirmation_code' =>
                    'Type CLEAN-UNLINKED-REGULAR-APPLICATIONS exactly to clean legacy unlinked Regular applications.',
            ]);
        }

        $result = $this->service->cleanupLegacyUnlinkedRegularApplications(
            $university->id,
            $request->user()->id
        );

        $message = $result['deleted'].' legacy unlinked Regular application(s) cleaned successfully.';
        if (($result['blocked'] ?? 0) > 0) {
            $message .= ' '.$result['blocked'].' record(s) were preserved because downstream processing references exist.';
        }

        return back()->with('success', $message);
    }


    public function deactivateAdmissionFormTemplate(
        Request $request,
        int $template
    ): RedirectResponse {
        $this->authorizeCleanup($request);

        $university = University::query()->firstOrFail();

        $request->validate([
            'confirmation_code' => [
                'required',
                'string',
                'max:120',
            ],
        ]);

        $records =
            $this->service
                ->listMaintenanceEntities($university->id);

        $record = collect(
            $records['college_admission_form_templates'] ?? []
        )->firstWhere('id', $template);

        if (! $record) {
            abort(404);
        }

        if (
            trim((string) $request->input('confirmation_code'))
                !== (string) $record['code']
        ) {
            throw ValidationException::withMessages([
                'confirmation_code' =>
                    'Type the exact Admission Form Template Code to confirm testing deactivation.',
            ]);
        }

        $this->service->deactivateAdmissionFormTemplateForTesting(
            $template,
            $university->id,
            $request->user()->id
        );

        return back()->with(
            'success',
            'Admission Form Template returned to DRAFT for testing corrections. Any public applicant access for this template was disabled.'
        );
    }

    public function bulkCleanup(
        Request $request
    ): RedirectResponse {
        $this->authorizeCleanup($request);

        $university = University::query()->firstOrFail();

        $validated = $request->validate([
            'type' => ['required', 'string', 'max:120'],
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'min:1'],
            'all' => ['nullable', 'boolean'],
            'confirmation_code' => ['required', 'string', 'max:160'],
        ]);

        $type = (string) $validated['type'];
        $expected = 'CLEAN-'.strtoupper(str_replace('_', '-', $type)).'-TEST-DATA';

        if (trim((string) $validated['confirmation_code']) !== $expected) {
            throw ValidationException::withMessages([
                'confirmation_code' => 'Type '.$expected.' exactly to run this module cleanup.',
            ]);
        }

        $all = (bool) ($validated['all'] ?? false);
        $requestedIds = collect($validated['ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($type === 'curriculum') {
            $query = Curriculum::query()
                ->where('university_id', $university->id)
                ->orderByDesc('id');

            if (! $all) {
                $query->whereIn('id', $requestedIds);
            }

            $rows = $query->get()
                ->filter(fn (Curriculum $curriculum) =>
                    count($this->service->curriculumCleanupPreview($curriculum)['downstream_references']) === 0
                )
                ->values();

            if ($rows->isEmpty()) {
                throw ValidationException::withMessages([
                    'ids' => 'No cleanable Curriculum records are available in the selected scope.',
                ]);
            }

            foreach ($rows as $curriculum) {
                $this->service->cleanupCurriculum(
                    $curriculum,
                    $request->user()->id
                );
            }

            return back()->with(
                'success',
                $rows->count().' cleanable Curriculum record(s) cleaned. Dependency-blocked records were preserved.'
            );
        }

        if ($type === 'academic_policies') {
            $query = AcademicPolicy::query()
                ->where('university_id', $university->id)
                ->orderByDesc('id');

            if (! $all) {
                $query->whereIn('id', $requestedIds);
            }

            $candidateIds = $query->pluck('id')->map(fn ($id) => (int) $id);
            $cleanedChains = 0;

            foreach ($candidateIds as $policyId) {
                $policy = AcademicPolicy::query()->find($policyId);
                if (! $policy) {
                    // A previously-cleaned selected version may have removed this whole version chain.
                    continue;
                }

                $preview = $this->service->academicPolicyCleanupPreview($policy);
                if (! ($preview['can_cleanup'] ?? false)) {
                    continue;
                }

                $this->service->cleanupAcademicPolicy(
                    $policy,
                    $request->user()->id
                );
                $cleanedChains++;
            }

            if ($cleanedChains === 0) {
                throw ValidationException::withMessages([
                    'ids' => 'No cleanable Academic Policy version chain is available in the selected scope.',
                ]);
            }

            return back()->with(
                'success',
                $cleanedChains.' Academic Policy test version chain(s) cleaned. Dependency-blocked chains were preserved.'
            );
        }

        $records = $this->service->listMaintenanceEntities($university->id);

        if (! array_key_exists($type, $records)) {
            throw ValidationException::withMessages([
                'type' => 'Unsupported cleanup module.',
            ]);
        }

        $moduleRows = collect($records[$type] ?? []);
        $candidateRows = $all
            ? $moduleRows
            : $moduleRows->whereIn('id', $requestedIds);

        $cleanableRows = $candidateRows
            ->filter(fn ($row) => ! (bool) ($row['blocked'] ?? false))
            ->values();

        if ($cleanableRows->isEmpty()) {
            throw ValidationException::withMessages([
                'ids' => 'No cleanable records are available in the selected module/scope.',
            ]);
        }

        $cleaned = 0;
        foreach ($cleanableRows as $row) {
            $this->service->cleanupMaster(
                $type,
                (int) $row['id'],
                $university->id,
                $request->user()->id
            );
            $cleaned++;
        }

        $preserved = $candidateRows->count() - $cleaned;
        $message = $cleaned.' test record(s) cleaned from '.str_replace('_', ' ', $type).'.';
        if ($preserved > 0) {
            $message .= ' '.$preserved.' blocked/protected record(s) were preserved.';
        }

        return back()->with('success', $message);
    }

    public function destroyMaster(
        Request $request,
        string $type,
        int $id
    ): RedirectResponse {
        $this->authorizeCleanup($request);

        $university = University::query()->firstOrFail();

        $request->validate([
            'confirmation_code' => [
                'required',
                'string',
                'max:120',
            ],
        ]);

        $records =
            $this->service
                ->listMaintenanceEntities($university->id);

        $record = collect($records[$type] ?? [])
            ->firstWhere('id', $id);

        if (! $record) {
            abort(404);
        }

        if (
            trim((string) $request->input(
                'confirmation_code'
            )) !== (string) $record['code']
        ) {
            throw ValidationException::withMessages([
                'confirmation_code' =>
                    'Type the exact record Code to confirm cleanup.',
            ]);
        }

        $this->service->cleanupMaster(
            $type,
            $id,
            $university->id,
            $request->user()->id
        );

        return back()->with(
            'success',
            'Selected test master data cleaned successfully.'
        );
    }

    private function confirmCode(
        Request $request,
        string $expected
    ): void {
        $request->validate([
            'confirmation_code' => [
                'required',
                'string',
                'max:120',
            ],
        ]);

        if (
            trim((string) $request->input(
                'confirmation_code'
            )) !== $expected
        ) {
            throw ValidationException::withMessages([
                'confirmation_code' =>
                    'Type the exact Code to confirm this test cleanup action.',
            ]);
        }
    }

    private function authorizeCleanup(Request $request): void
    {
        abort_unless(
            $request->user()->hasPermission(
                'test_data_cleanup.manage'
            ),
            403
        );
    }
}
