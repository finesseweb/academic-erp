<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Admission;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Services\StudentEnrollmentEligibilityService;
use App\Services\StudentEnrollmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollegeStudentEnrollmentController extends Controller
{
    public function index(Request $request, College $college, StudentEnrollmentEligibilityService $eligibility): Response
    {
        abort_unless($request->user()->hasCollegePermission('college_student_enrollment.view', $college->id), 403);

        $sessions = AcademicSession::query()
            ->where('university_id', $college->university_id)->where('status', 'ACTIVE')
            ->orderByDesc('is_current')->orderByDesc('starts_on')
            ->get(['id', 'name', 'code', 'is_current']);
        $currentSessionId = (int) ($sessions->firstWhere('is_current', true)?->id ?? $sessions->first()?->id ?? 0);
        $sessionId = (int) $request->query('session_id', $currentSessionId);
        $offeringId = (int) $request->query('offering_id', 0);

        $offerings = CollegeProgramOffering::query()
            ->with('programTemplate:id,name,code')
            ->where('college_id', $college->id)
            ->when($sessionId > 0, fn ($q) => $q->where('academic_session_id', $sessionId))
            ->orderBy('program_template_id')->get(['id', 'program_template_id', 'academic_session_id', 'status'])
            ->map(fn ($o) => ['id' => (int) $o->id, 'name' => $o->programTemplate?->name, 'code' => $o->programTemplate?->code, 'status' => $o->status])->values();

        if ($offeringId > 0 && ! $offerings->contains('id', $offeringId)) {
            $offeringId = 0;
        }

        $filters = [
            'session_id' => $sessionId,
            'offering_id' => $offeringId,
            'q' => trim((string) $request->query('q', '')),
            'status' => strtoupper(trim((string) $request->query('status', ''))),
            'per_page' => (int) $request->query('per_page', 25),
        ];

        return Inertia::render('college-student-enrollments/index', [
            'college' => $college->only(['id', 'name', 'code']),
            'sessions' => $sessions,
            'offerings' => $offerings,
            'students' => $eligibility->queue($college, $filters),
            'filters' => $filters,
            'can' => ['enroll' => $request->user()->hasCollegePermission('college_student_enrollment.enroll', $college->id)],
        ]);
    }

    public function store(Request $request, College $college, Admission $admission, StudentEnrollmentService $enrollment): RedirectResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_student_enrollment.enroll', $college->id), 403);
        $enrollment->enroll($college, $admission, (int) $request->user()->id, $request->ip());

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Student enrolled successfully.',
        ]);
    }
}
