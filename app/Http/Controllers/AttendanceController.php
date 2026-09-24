<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveAttendanceRequest;
use App\Models\AttendanceRegister;
use App\Models\ClassSchedule;
use App\Models\College;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        $this->authorizeCollege($request, $college, 'college_attendance.view');
        $schedules = ClassSchedule::query()
            ->with(['attendanceRegister:id,class_schedule_id,status,revision_no', 'timetableEntry.facultyAllocation.faculty:id,name', 'timetableEntry.facultyAllocation.section:id,name,code', 'timetableEntry.facultyAllocation.courseOffering.batch:id,name,code', 'timetableEntry.facultyAllocation.courseOffering.curriculumCourseMapping.course:id,name,code'])
            ->where('status', '!=', 'CANCELLED')
            ->whereHas('timetableEntry.facultyAllocation.courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))
            ->orderByDesc('class_date')->paginate(30)->withQueryString();

        return Inertia::render('attendance/index', [
            'college' => $college->only(['id', 'name', 'code', 'status']), 'schedules' => $schedules,
            'can' => ['manage' => $request->user()->hasCollegePermission('college_attendance.manage', $college->id)],
        ]);
    }

    public function edit(Request $request, College $college, ClassSchedule $classSchedule, AttendanceService $service): Response
    {
        $this->authorizeCollege($request, $college, 'college_attendance.view');
        $context = $service->context($college, $classSchedule);
        $roster = $service->roster($college, $classSchedule);
        $register = AttendanceRegister::with('records')->where('class_schedule_id', $classSchedule->id)->first();
        $saved = $register?->records->keyBy('student_enrollment_id') ?? collect();
        $classSchedule->loadMissing(['timetableEntry.facultyAllocation.faculty:id,name', 'timetableEntry.facultyAllocation.section:id,name,code', 'timetableEntry.facultyAllocation.courseOffering.batch:id,name,code', 'timetableEntry.facultyAllocation.courseOffering.curriculumCourseMapping.course:id,name,code', 'room:id,code,name']);

        $summary = $this->summary(
            $classSchedule,
            $roster->pluck('id')->all(),
            $context['policy']->attendanceRule->rounding_rule,
            $context['policy']->attendanceRule->calculation_level,
        );

        return Inertia::render('attendance/edit', [
            'college' => $college->only(['id', 'name', 'code']), 'classSchedule' => $classSchedule,
            'register' => $register, 'policy' => $context['policy']->only(['id', 'name', 'code', 'version']) + ['attendance_rule' => $context['policy']->attendanceRule],
            'roster' => $roster->map(function ($enrollment) use ($saved) {
                $record = $saved->get($enrollment->id);

                return ['id' => $enrollment->id, 'class_roll_no' => $enrollment->class_roll_no, 'student' => $enrollment->student, 'attendance_status' => $record ? $record->attendance_status : 'PRESENT', 'remarks' => $record ? $record->remarks : null];
            }),
            'summary' => $summary,
            'can' => ['manage' => $request->user()->hasCollegePermission('college_attendance.manage', $college->id), 'finalize' => $request->user()->hasCollegePermission('college_attendance.finalize', $college->id), 'correct' => $request->user()->hasCollegePermission('college_attendance.correct', $college->id)],
        ]);
    }

    public function save(SaveAttendanceRequest $request, College $college, ClassSchedule $classSchedule, AttendanceService $service): RedirectResponse
    {
        $permission = $request->validated('action') === 'FINALIZE' ? 'college_attendance.finalize' : 'college_attendance.manage';
        $this->authorizeCollege($request, $college, $permission);
        $service->save($college, $classSchedule, $request->validated(), $request->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => $request->validated('action') === 'FINALIZE' ? 'Attendance finalized and class marked completed.' : 'Attendance draft saved.']);
    }

    public function reopen(Request $request, College $college, AttendanceRegister $attendanceRegister, AttendanceService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_attendance.correct');
        $data = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $service->reopen($college, $attendanceRegister, $data['reason'], $request->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Attendance reopened for an audited correction.']);
    }

    /**
     * Build finalized attendance totals using the calculation scope selected by
     * the resolved Academic Policy. Raw attendance remains immutable; only the
     * aggregation boundary changes.
     *
     * @param  array<int, int>  $enrollmentIds
     * @return array<int, array{held: int, attended: int, percent: float|int}>
     */
    private function summary(
        ClassSchedule $schedule,
        array $enrollmentIds,
        string $rounding,
        string $calculationLevel,
    ): array {
        if (! $enrollmentIds) {
            return [];
        }

        $allocation = $schedule->timetableEntry->facultyAllocation;
        $courseOffering = $allocation->courseOffering;
        $programmeOfferingId = $courseOffering->batch->college_program_offering_id;

        $query = DB::table('attendance_records as ar')
            ->join('attendance_registers as reg', 'reg.id', '=', 'ar.attendance_register_id')
            ->join('class_schedules as cs', 'cs.id', '=', 'reg.class_schedule_id')
            ->join('timetable_entries as te', 'te.id', '=', 'cs.timetable_entry_id')
            ->join('faculty_allocations as fa', 'fa.id', '=', 'te.faculty_allocation_id')
            ->join('course_offerings as co', 'co.id', '=', 'fa.course_offering_id')
            ->join('batches as b', 'b.id', '=', 'co.batch_id')
            ->where('reg.status', 'FINALIZED')
            ->whereIn('ar.student_enrollment_id', $enrollmentIds);

        match ($calculationLevel) {
            'TERM' => $this->applyTermAttendanceScope($query, $courseOffering->id, $programmeOfferingId),
            'OVERALL' => $query->where('b.college_program_offering_id', $programmeOfferingId),
            default => $query->where('co.id', $courseOffering->id),
        };

        $rows = $query
            ->selectRaw("ar.student_enrollment_id, COUNT(*) as held, SUM(CASE WHEN ar.attendance_status IN ('PRESENT','LATE','EXCUSED') THEN 1 ELSE 0 END) as attended")
            ->groupBy('ar.student_enrollment_id')
            ->get();

        return $rows->mapWithKeys(function ($row) use ($rounding) {
            $raw = $row->held ? 100 * $row->attended / $row->held : 0;
            $percent = match ($rounding) {
                'FLOOR' => floor($raw), 'CEIL' => ceil($raw), 'NEAREST' => round($raw), default => round($raw, 2)
            };

            return [$row->student_enrollment_id => ['held' => (int) $row->held, 'attended' => (int) $row->attended, 'percent' => $percent]];
        })->all();
    }

    private function applyTermAttendanceScope($query, int $courseOfferingId, int $programmeOfferingId): mixed
    {
        $termId = DB::table('course_offerings as current_co')
            ->join('curriculum_course_mappings as current_ccm', 'current_ccm.id', '=', 'current_co.curriculum_course_mapping_id')
            ->join('curriculum_slots as current_slot', 'current_slot.id', '=', 'current_ccm.curriculum_slot_id')
            ->where('current_co.id', $courseOfferingId)
            ->value('current_slot.curriculum_term_id');

        if (! $termId) {
            // A legitimate Course Offering should always resolve a Curriculum
            // Term. Fail closed instead of silently widening TERM to OVERALL.
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->join('curriculum_course_mappings as ccm', 'ccm.id', '=', 'co.curriculum_course_mapping_id')
            ->join('curriculum_slots as slot', 'slot.id', '=', 'ccm.curriculum_slot_id')
            ->where('b.college_program_offering_id', $programmeOfferingId)
            ->where('slot.curriculum_term_id', $termId);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
