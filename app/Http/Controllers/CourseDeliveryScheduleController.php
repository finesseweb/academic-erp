<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\College;
use App\Models\CollegeRoom;
use App\Models\FacultyAllocation;
use App\Models\TimetableEntry;
use App\Services\CourseDeliverySchedulingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseDeliveryScheduleController extends Controller
{
    public function rooms(Request $r, College $college): Response
    {
        $this->auth($r, $college, 'college_room.view');

        return Inertia::render('course-delivery/rooms', ['college' => $college->only(['id', 'name', 'code', 'status']), 'rooms' => CollegeRoom::where('college_id', $college->id)->orderBy('building')->orderBy('code')->get(), 'can' => ['manage' => $r->user()->hasCollegePermission('college_room.manage', $college->id)]]);
    }

    public function saveRoom(Request $r, College $college, CourseDeliverySchedulingService $s): RedirectResponse
    {
        $this->auth($r, $college, 'college_room.manage');
        $s->saveRoom($college, $this->roomData($r), $r->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Room created.']);
    }

    public function updateRoom(Request $r, College $college, CollegeRoom $room, CourseDeliverySchedulingService $s): RedirectResponse
    {
        $this->auth($r, $college, 'college_room.manage');
        $s->saveRoom($college, $this->roomData($r), $r->user()->id, $room);

        return back()->with('toast', ['type' => 'success', 'message' => 'Room updated.']);
    }

    public function timetables(Request $r, College $college): Response
    {
        $this->auth($r, $college, 'college_timetable.view');
        $allocations = FacultyAllocation::with(['faculty:id,name,email', 'section:id,name,code', 'courseOffering.batch.offering.programTemplate:id,name,code', 'courseOffering.batch.offering.academicSession:id,name,code,is_current', 'courseOffering.batch:id,name,code', 'courseOffering.curriculumCourseMapping.course:id,name,code', 'courseOffering.curriculumCourseMapping.slot.term:id,name,sequence_no', 'courseOffering.curriculumCourseMapping.discipline:id,name,code'])->where('status', 'ACTIVE')->whereHas('courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))->orderByDesc('id')->get();
        $entries = TimetableEntry::with(['facultyAllocation.faculty:id,name', 'facultyAllocation.section:id,name,code', 'room:id,code,name', 'classSchedules:id,timetable_entry_id,status'])->whereHas('facultyAllocation.courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))->orderBy('day_of_week')->orderBy('start_time')->get();

        return Inertia::render('course-delivery/timetables', ['college' => $college->only(['id', 'name', 'code', 'status']), 'allocations' => $allocations, 'rooms' => CollegeRoom::where('college_id', $college->id)->orderBy('code')->get(), 'entries' => $entries, 'can' => ['manage' => $r->user()->hasCollegePermission('college_timetable.manage', $college->id), 'enable' => $r->user()->hasCollegePermission('college_timetable.enable', $college->id), 'disable' => $r->user()->hasCollegePermission('college_timetable.disable', $college->id)]]);
    }

    public function saveTimetable(Request $r, College $college, CourseDeliverySchedulingService $s): RedirectResponse
    {
        $this->auth($r, $college, 'college_timetable.manage');
        $s->saveTimetable($college, $this->timetableData($r), $r->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Timetable entry created as INACTIVE.']);
    }

    public function updateTimetable(Request $r, College $college, TimetableEntry $entry, CourseDeliverySchedulingService $s): RedirectResponse
    {
        $this->auth($r, $college, 'college_timetable.manage');
        $s->saveTimetable($college, $this->timetableData($r), $r->user()->id, $entry);

        return back()->with('toast', ['type' => 'success', 'message' => 'Timetable entry updated.']);
    }

    public function timetableStatus(Request $r, College $college, TimetableEntry $entry, CourseDeliverySchedulingService $s): RedirectResponse
    {
        $data = $r->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]]);
        $this->auth($r, $college, $data['status'] === 'ACTIVE' ? 'college_timetable.enable' : 'college_timetable.disable');
        $s->timetableStatus($college, $entry, $data['status'], $r->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Timetable status updated.']);
    }

    public function classes(Request $r, College $college): Response
    {
        $this->auth($r, $college, 'college_class_schedule.view');
        $entries = TimetableEntry::with(['facultyAllocation.faculty:id,name', 'facultyAllocation.section:id,name,code', 'facultyAllocation.courseOffering.batch:id,name,code', 'facultyAllocation.courseOffering.curriculumCourseMapping.course:id,name,code', 'room:id,code,name'])->where('status', 'ACTIVE')->whereHas('facultyAllocation', fn ($q) => $q->where('status', 'ACTIVE'))->whereHas('facultyAllocation.courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))->orderBy('day_of_week')->orderBy('start_time')->get();
        $classes = ClassSchedule::with(['timetableEntry.facultyAllocation.faculty:id,name', 'timetableEntry.facultyAllocation.section:id,name,code', 'timetableEntry.facultyAllocation.courseOffering.curriculumCourseMapping.course:id,name,code', 'room:id,code,name'])->whereHas('timetableEntry.facultyAllocation.courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))->orderByDesc('class_date')->paginate(30)->withQueryString();

        return Inertia::render('course-delivery/class-schedules', ['college' => $college->only(['id', 'name', 'code', 'status']), 'entries' => $entries, 'classes' => $classes, 'can' => ['manage' => $r->user()->hasCollegePermission('college_class_schedule.manage', $college->id), 'status' => $r->user()->hasCollegePermission('college_class_schedule.status', $college->id)]]);
    }

    public function saveClass(Request $r, College $college, CourseDeliverySchedulingService $s): RedirectResponse
    {
        $this->auth($r, $college, 'college_class_schedule.manage');
        $data = $r->validate(['timetable_entry_id' => ['required', 'integer'], 'class_date' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $s->createClass($college, $data, $r->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Class scheduled.']);
    }

    public function updateClass(Request $r, College $college, ClassSchedule $classSchedule, CourseDeliverySchedulingService $s): RedirectResponse
    {
        $this->auth($r, $college, 'college_class_schedule.manage');
        $data = $r->validate(['timetable_entry_id' => ['required', 'integer'], 'class_date' => ['required', 'date'], 'notes' => ['nullable', 'string', 'max:2000']]);
        $s->updateClass($college, $classSchedule, $data, $r->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Scheduled class updated.']);
    }

    public function classStatus(Request $r, College $college, ClassSchedule $classSchedule, CourseDeliverySchedulingService $s): RedirectResponse
    {
        $this->auth($r, $college, 'college_class_schedule.status');
        $data = $r->validate(['status' => ['required', Rule::in(['SCHEDULED', 'COMPLETED', 'CANCELLED'])]]);
        $s->classStatus($college, $classSchedule, $data['status'], $r->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Class status updated.']);
    }

    /** @return array<string, mixed> */
    private function roomData(Request $r): array
    {
        return $r->validate(['code' => ['required', 'string', 'max:50'], 'name' => ['required', 'string', 'max:150'], 'building' => ['nullable', 'string', 'max:150'], 'floor' => ['nullable', 'string', 'max:50'], 'room_type' => ['required', Rule::in(['CLASSROOM', 'LAB', 'SEMINAR', 'OTHER'])], 'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'], 'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])], 'notes' => ['nullable', 'string', 'max:2000']]);
    }

    /** @return array<string, mixed> */
    private function timetableData(Request $r): array
    {
        return $r->validate(['faculty_allocation_id' => ['required', 'integer'], 'room_id' => ['nullable', 'integer'], 'day_of_week' => ['required', 'integer', 'between:1,7'], 'start_time' => ['required', 'date_format:H:i'], 'end_time' => ['required', 'date_format:H:i'], 'effective_from' => ['required', 'date'], 'effective_until' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:2000']]);
    }

    private function auth(Request $r, College $c, string $p): void
    {
        abort_unless($r->user()->hasCollegePermission($p, $c->id), 403);
    }
}
