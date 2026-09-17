<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\Admission;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Services\FeeClearanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollegeFeeClearanceController extends Controller
{
    public function index(Request $request, College $college, FeeClearanceService $clearance): Response
    {
        abort_unless($request->user()->hasCollegePermission('college_fee_clearance.view', $college->id), 403);

        $sessions = AcademicSession::query()
            ->where('university_id', $college->university_id)
            ->where('status', 'ACTIVE')
            ->orderByDesc('is_current')
            ->orderByDesc('starts_on')
            ->get(['id', 'name', 'code', 'is_current']);

        $currentSessionId = (int) ($sessions->firstWhere('is_current', true)?->id ?? $sessions->first()?->id ?? 0);
        $sessionId = (int) $request->query('session_id', $currentSessionId);
        $offeringId = (int) $request->query('offering_id', 0);
        $q = trim((string) $request->query('q', ''));
        $status = strtoupper(trim((string) $request->query('status', '')));
        $perPage = (int) $request->query('per_page', 25);

        $offerings = CollegeProgramOffering::query()
            ->with('programTemplate:id,name,code')
            ->where('college_id', $college->id)
            ->when($sessionId > 0, fn ($query) => $query->where('academic_session_id', $sessionId))
            ->orderBy('program_template_id')
            ->get(['id', 'program_template_id', 'academic_session_id', 'status'])
            ->map(fn (CollegeProgramOffering $offering) => [
                'id' => (int) $offering->id,
                'name' => $offering->programTemplate?->name,
                'code' => $offering->programTemplate?->code,
                'status' => $offering->status,
            ])
            ->values();

        // A stale/tampered offering must never broaden or cross the selected Session/College scope.
        if ($offeringId > 0 && ! $offerings->contains('id', $offeringId)) {
            $offeringId = 0;
        }

        $students = $clearance->register($college, [
            'session_id' => $sessionId,
            'offering_id' => $offeringId,
            'q' => $q,
            'status' => $status,
            'per_page' => $perPage,
        ]);

        $selected = null;
        $admissionId = (int) $request->query('admission_id', 0);
        if ($admissionId > 0) {
            $admission = Admission::query()->where('college_id', $college->id)->findOrFail($admissionId);
            $selected = $clearance->forAdmission($college, $admission, $sessionId, $offeringId);
        }

        return Inertia::render('college-fee-clearance/index', [
            'college' => $college->only(['id', 'name', 'code']),
            'sessions' => $sessions,
            'offerings' => $offerings,
            'students' => $students,
            'clearance' => $selected,
            'filters' => [
                'session_id' => $sessionId,
                'offering_id' => $offeringId ?: null,
                'q' => $q,
                'status' => $status,
                'per_page' => $perPage,
                'admission_id' => $admissionId ?: null,
            ],
        ]);
    }
}
