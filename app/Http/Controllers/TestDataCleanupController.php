<?php

namespace App\Http\Controllers;

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

        return Inertia::render(
            'admin/system-maintenance/test-data-cleanup',
            [
                'enabled' =>
                    (bool) config('test-data-cleanup.enabled'),
                'environment' => app()->environment(),
                'curricula' => $curricula,
                'entities' =>
                    $this->service
                        ->listMaintenanceEntities($university->id),
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
