<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollegeAcademicCalendarRequest;
use App\Http\Requests\StoreCollegeCalendarOverrideRequest;
use App\Http\Requests\UpdateCollegeAcademicCalendarRequest;
use App\Http\Requests\UpdateCollegeCalendarOverrideRequest;
use App\Models\AcademicCalendar;
use App\Models\College;
use App\Models\CollegeAcademicCalendar;
use App\Models\CollegeProgramOffering;
use App\Models\CollegeCalendarOverride;
use App\Services\CollegeAcademicCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeAcademicCalendarController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        $this->authorizeCollege($request, $college, 'college_academic_calendar.view');

        $calendars = CollegeAcademicCalendar::query()
            ->with([
                'universityCalendar:id,university_id,academic_session_id,name,code,status,notes',
                'universityCalendar.academicSession:id,name,code,starts_on,ends_on,status,is_current',
                'universityCalendar.events',
                'overrides',
            ])
            ->where('college_id', $college->id)
            ->orderByDesc('id')
            ->get();

        $adoptedIds = $calendars->pluck('university_academic_calendar_id');
        $collegeSessionIds = CollegeProgramOffering::query()
            ->where('college_id', $college->id)
            ->pluck('academic_session_id')
            ->unique();

        $available = AcademicCalendar::query()
            ->with(['academicSession:id,name,code,starts_on,ends_on,status,is_current', 'events'])
            ->where('university_id', $college->university_id)
            ->whereIn('academic_session_id', $collegeSessionIds)
            ->where('status', 'ACTIVE')
            ->when($adoptedIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $adoptedIds))
            ->orderByDesc('academic_session_id')
            ->get();

        return Inertia::render('college-academic-calendars/index', [
            'college' => $college->only(['id', 'name', 'code', 'status', 'university_id']),
            'calendars' => $calendars,
            'availableCalendars' => $available,
            'summary' => [
                'total' => $calendars->count(),
                'active' => $calendars->where('status', 'ACTIVE')->count(),
                'inactive' => $calendars->where('status', 'INACTIVE')->count(),
                'overrides' => $calendars->sum(fn ($c) => $c->overrides->where('status', 'ACTIVE')->count()),
            ],
            'can' => [
                'create' => $request->user()->hasCollegePermission('college_academic_calendar.create', $college->id),
                'update' => $request->user()->hasCollegePermission('college_academic_calendar.update', $college->id),
                'enable' => $request->user()->hasCollegePermission('college_academic_calendar.enable', $college->id),
                'disable' => $request->user()->hasCollegePermission('college_academic_calendar.disable', $college->id),
                'overrideCreate' => $request->user()->hasCollegePermission('college_academic_calendar.override_create', $college->id),
                'overrideUpdate' => $request->user()->hasCollegePermission('college_academic_calendar.override_update', $college->id),
                'overrideDisable' => $request->user()->hasCollegePermission('college_academic_calendar.override_disable', $college->id),
            ],
        ]);
    }

    public function store(StoreCollegeAcademicCalendarRequest $request, College $college, CollegeAcademicCalendarService $service): RedirectResponse
    {
        $service->create($college, $request->validated(), $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => 'University Academic Calendar adopted for this College. Review it, then activate the College Calendar.']);
    }

    public function update(UpdateCollegeAcademicCalendarRequest $request, College $college, CollegeAcademicCalendar $collegeAcademicCalendar, CollegeAcademicCalendarService $service): RedirectResponse
    {
        $service->update($collegeAcademicCalendar, $college, $request->validated(), $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => 'College Academic Calendar updated.']);
    }

    public function status(Request $request, College $college, CollegeAcademicCalendar $collegeAcademicCalendar, CollegeAcademicCalendarService $service): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]])['status'];
        $this->authorizeCollege($request, $college, $status === 'ACTIVE' ? 'college_academic_calendar.enable' : 'college_academic_calendar.disable');
        $service->changeStatus($collegeAcademicCalendar, $college, $status, $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => $status === 'ACTIVE' ? 'College Academic Calendar activated.' : 'College Academic Calendar deactivated.']);
    }

    public function storeOverride(StoreCollegeCalendarOverrideRequest $request, College $college, CollegeAcademicCalendar $collegeAcademicCalendar, CollegeAcademicCalendarService $service): RedirectResponse
    {
        $service->createOverride($collegeAcademicCalendar, $college, $request->validated(), $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => 'College override saved. The University event remains the governance source.']);
    }

    public function updateOverride(UpdateCollegeCalendarOverrideRequest $request, College $college, CollegeAcademicCalendar $collegeAcademicCalendar, CollegeCalendarOverride $override, CollegeAcademicCalendarService $service): RedirectResponse
    {
        $service->updateOverride($collegeAcademicCalendar, $override, $college, $request->validated(), $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => 'College event override updated.']);
    }

    public function overrideStatus(Request $request, College $college, CollegeAcademicCalendar $collegeAcademicCalendar, CollegeCalendarOverride $override, CollegeAcademicCalendarService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_academic_calendar.override_disable');
        $status = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]])['status'];
        $service->changeOverrideStatus($collegeAcademicCalendar, $override, $college, $status, $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => $status === 'ACTIVE' ? 'College override re-enabled.' : 'College override disabled; the University event is effective again.']);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
