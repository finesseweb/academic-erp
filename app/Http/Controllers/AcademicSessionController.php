<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\University;
use App\Services\AcademicSessionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AcademicSessionController extends Controller
{
    public function __construct(private AcademicSessionService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('academic_session.view'), 403);
        $university = University::firstOrFail();
        $sessions = AcademicSession::where('university_id', $university->id)->orderByDesc('starts_on')->get();

        return Inertia::render('academic-sessions/index', ['sessions' => $sessions, 'can' => ['create' => $request->user()->hasPermission('academic_session.create'), 'update' => $request->user()->hasPermission('academic_session.update'), 'close' => $request->user()->hasPermission('academic_session.close'), 'setCurrent' => $request->user()->hasPermission('academic_session.set_current')]]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('academic_session.create'), 403);
        $university = University::firstOrFail();
        $data = $request->validate($this->rules($university->id));
        $this->service->create($data, $university->id, $request->user()->id, $request->ip());

        return back()->with('success', 'Academic session created.');
    }

    public function update(Request $request, AcademicSession $academicSession): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('academic_session.update'), 403);
        $this->owned($academicSession);
        $data = $request->validate($this->rules($academicSession->university_id, $academicSession));
        $this->service->update($academicSession, $data, $request->user()->id, $request->ip());

        return back()->with('success', 'Academic session updated.');
    }

    public function status(Request $request, AcademicSession $academicSession): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('academic_session.close'), 403);
        $this->owned($academicSession);
        $data = $request->validate(['status' => ['required', Rule::in(['PLANNED', 'ACTIVE', 'CLOSED', 'ARCHIVED'])]]);
        $this->service->changeStatus($academicSession, $data['status'], $request->user()->id, $request->ip());

        return back()->with('success', 'Academic session status updated.');
    }

    public function setCurrent(Request $request, AcademicSession $academicSession): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('academic_session.set_current'), 403);
        $this->owned($academicSession);
        $this->service->setCurrent($academicSession, $request->user()->id, $request->ip());

        return back()->with('success', 'Current academic session updated.');
    }

    private function rules(int $universityId, ?AcademicSession $session = null): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'code' => ['required', 'string', 'max:40', Rule::unique('academic_sessions')->where('university_id', $universityId)->ignore($session)], 'starts_on' => ['required', 'date'], 'ends_on' => ['required', 'date', 'after_or_equal:starts_on'], 'status' => ['required', Rule::in(['PLANNED', 'ACTIVE', 'CLOSED', 'ARCHIVED'])]];
    }

    private function owned(AcademicSession $session): void
    {
        abort_unless($session->university_id === University::firstOrFail()->id, 404);
    }
}
