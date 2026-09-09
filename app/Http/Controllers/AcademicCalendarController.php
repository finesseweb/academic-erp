<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcademicCalendarEventRequest;
use App\Http\Requests\StoreAcademicCalendarRequest;
use App\Http\Requests\UpdateAcademicCalendarEventRequest;
use App\Http\Requests\UpdateAcademicCalendarRequest;
use App\Models\AcademicCalendar;
use App\Models\AcademicCalendarEvent;
use App\Models\AcademicCalendarTermPeriod;
use App\Models\Curriculum;
use App\Models\AcademicSession;
use App\Models\University;
use App\Services\AcademicCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AcademicCalendarController extends Controller
{
    public function __construct(private AcademicCalendarService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('academic_calendar.view'), 403);

        $university = University::query()->firstOrFail();
        $sessions = AcademicSession::query()
            ->where('university_id', $university->id)
            ->where('status', 'ACTIVE')
            ->orderByDesc('is_current')
            ->orderByDesc('starts_on')
            ->get([
                'id',
                'name',
                'code',
                'starts_on',
                'ends_on',
                'status',
                'is_current',
            ]);

        $calendars = AcademicCalendar::query()
            ->with(['academicSession:id,name,code,starts_on,ends_on,status,is_current', 'events.termPeriod.curriculumTerm.curriculum.programTemplate:id,name,code', 'termPeriods.curriculumTerm.curriculum.programTemplate:id,name,code'])
            ->where('university_id', $university->id)
            ->orderByDesc('id')
            ->get();

        return Inertia::render('academic-calendars/index', [
            'sessions' => $sessions,
            'calendars' => $calendars,
            'curriculaBySession' => Curriculum::query()->with(['programTemplate:id,name,code','terms'=>fn($q)=>$q->where('status','ACTIVE')->orderBy('sequence_no')])->where('university_id',$university->id)->currentApproved()->get()->groupBy('academic_session_id'),
            'can' => [
                'create' => $request->user()->hasPermission('academic_calendar.create'),
                'update' => $request->user()->hasPermission('academic_calendar.update'),
                'disable' => $request->user()->hasPermission('academic_calendar.disable'),
                'eventCreate' => $request->user()->hasPermission('academic_calendar.event_create'),
                'eventUpdate' => $request->user()->hasPermission('academic_calendar.event_update'),
                'eventDisable' => $request->user()->hasPermission('academic_calendar.event_disable'),
                'periodManage' => $request->user()->hasPermission('academic_calendar.event_update'),
            ],
        ]);
    }

    public function store(StoreAcademicCalendarRequest $request): RedirectResponse
    {
        $university = University::query()->firstOrFail();
        $data = $request->validated();
        $this->validateSessionOwnership($university->id, (int) $data['academic_session_id']);
        $request->validate([
            'code' => [Rule::unique('academic_calendars', 'code')->where('university_id', $university->id)],
        ]);

        $this->service->create($data, $university->id, $request->user()->id, $request->ip());

        return back()->with('success', 'Academic Calendar created.');
    }

    public function update(UpdateAcademicCalendarRequest $request, AcademicCalendar $academicCalendar): RedirectResponse
    {
        $this->owned($academicCalendar);
        $request->validate([
            'code' => [Rule::unique('academic_calendars', 'code')->where('university_id', $academicCalendar->university_id)->ignore($academicCalendar)],
        ]);
        $this->service->update($academicCalendar, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('success', 'Academic Calendar updated.');
    }

    public function status(Request $request, AcademicCalendar $academicCalendar): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('academic_calendar.disable'), 403);
        $this->owned($academicCalendar);
        $data = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]]);
        $this->service->changeStatus($academicCalendar, $data['status'], $request->user()->id, $request->ip());

        return back()->with('success', 'Academic Calendar status updated.');
    }

    public function storeEvent(StoreAcademicCalendarEventRequest $request, AcademicCalendar $academicCalendar): RedirectResponse
    {
        $this->owned($academicCalendar);
        $this->service->createEvent($academicCalendar, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('success', 'Calendar event added.');
    }

    public function updateEvent(UpdateAcademicCalendarEventRequest $request, AcademicCalendar $academicCalendar, AcademicCalendarEvent $event): RedirectResponse
    {
        $this->owned($academicCalendar);
        $this->service->updateEvent($academicCalendar, $event, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('success', 'Calendar event updated.');
    }

    public function eventStatus(Request $request, AcademicCalendar $academicCalendar, AcademicCalendarEvent $event): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('academic_calendar.event_disable'), 403);
        $this->owned($academicCalendar);
        $data = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]]);
        $this->service->changeEventStatus($academicCalendar, $event, $data['status'], $request->user()->id, $request->ip());

        return back()->with('success', 'Calendar event status updated.');
    }


    public function storeTermPeriod(Request $request, AcademicCalendar $academicCalendar): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('academic_calendar.event_update'), 403);
        $this->owned($academicCalendar);
        $data=$request->validate(['curriculum_term_id'=>['required','integer','exists:curriculum_terms,id'],'start_date'=>['required','date'],'end_date'=>['required','date','after_or_equal:start_date'],'allow_college_override'=>['required','boolean']]);
        $this->service->upsertTermPeriod($academicCalendar, null, $data, $request->user()->id, $request->ip());
        return back()->with('success','Academic period saved.');
    }

    public function updateTermPeriod(Request $request, AcademicCalendar $academicCalendar, AcademicCalendarTermPeriod $period): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('academic_calendar.event_update'), 403);
        $this->owned($academicCalendar);
        $data=$request->validate(['start_date'=>['required','date'],'end_date'=>['required','date','after_or_equal:start_date'],'allow_college_override'=>['required','boolean']]);
        $this->service->upsertTermPeriod($academicCalendar, $period, $data, $request->user()->id, $request->ip());
        return back()->with('success','Academic period updated.');
    }

    private function owned(AcademicCalendar $calendar): void
    {
        abort_unless((int) $calendar->university_id === (int) University::query()->firstOrFail()->id, 404);
    }

    private function validateSessionOwnership(int $universityId, int $sessionId): void
    {
        abort_unless(
            AcademicSession::query()->whereKey($sessionId)->where('university_id', $universityId)->exists(),
            422
        );
    }
}
