<?php

namespace App\Http\Controllers;

use App\Models\AttendanceExceptionRequest;
use App\Models\College;
use App\Models\CourseOffering;
use App\Models\StudentAttendanceEligibility;
use App\Models\StudentEnrollment;
use App\Services\AttendanceEligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceEligibilityController extends Controller
{
    public function index(Request $request, College $college, AttendanceEligibilityService $service): Response
    {
        $this->authorizeCollege($request, $college, 'college_attendance_eligibility.view');
        $offerings = CourseOffering::with(['batch:id,college_program_offering_id,name,code', 'curriculumCourseMapping.course:id,name,code'])
            ->whereHas('batch.offering', fn ($q) => $q->where('college_id', $college->id))->where('status', 'ACTIVE')->orderByDesc('id')->get();
        $selected = $offerings->firstWhere('id', (int) $request->integer('course_offering_id')) ?? $offerings->first();
        $rows = collect();
        if ($selected) {
            $enrollments = StudentEnrollment::with('student:id,full_name,student_uid,university_roll_no')->where('college_id', $college->id)->where('college_program_offering_id', $selected->batch->college_program_offering_id)->where('batch_id', $selected->batch_id)->where('status', 'ENROLLED')->whereExists(fn ($q) => $q->selectRaw('1')->from('student_enrollment_course_choices as sec')->whereColumn('sec.student_enrollment_id', 'student_enrollments.id')->where('sec.curriculum_course_mapping_id', $selected->curriculum_course_mapping_id))->orderBy('class_roll_no')->get();
            $saved = StudentAttendanceEligibility::where('course_offering_id', $selected->id)->get()->keyBy('student_enrollment_id');
            $rows = $enrollments->map(function ($enrollment) use ($college, $selected, $service, $saved) {
                try {
                    $evaluation = $service->evaluate($college, $enrollment, $selected);
                } catch (ValidationException) {
                    $evaluation = null;
                }

                return ['enrollment' => $enrollment, 'evaluation' => $evaluation ? collect($evaluation)->except(['policy', 'rule'])->all() : null, 'policy' => $evaluation ? ['id' => $evaluation['policy']->id, 'code' => $evaluation['policy']->code, 'rule' => $evaluation['rule']] : null, 'finalized' => $saved->get($enrollment->id)];
            });
        }
        $requests = AttendanceExceptionRequest::with(['enrollment.student:id,full_name,student_uid', 'courseOffering.curriculumCourseMapping.course:id,name,code'])->where('college_id', $college->id)->when($selected, fn ($q) => $q->where('course_offering_id', $selected->id))->latest()->get();

        return Inertia::render('attendance/eligibility', ['college' => $college->only(['id', 'name', 'code']), 'offerings' => $offerings, 'selectedOfferingId' => $selected?->id, 'students' => $rows, 'requests' => $requests, 'can' => ['request' => $request->user()->hasCollegePermission('college_attendance_exception.request', $college->id), 'decide' => $request->user()->hasCollegePermission('college_attendance_exception.decide', $college->id), 'finalize' => $request->user()->hasCollegePermission('college_attendance_eligibility.finalize', $college->id)]]);
    }

    public function requestException(Request $request, College $college, StudentEnrollment $studentEnrollment, CourseOffering $courseOffering, AttendanceEligibilityService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_attendance_exception.request');
        $data = $request->validate(['type' => ['required', 'in:CONDONATION,SPECIAL_EXEMPTION'], 'reason' => ['required', 'string', 'min:10', 'max:4000'], 'supporting_reference' => ['nullable', 'string', 'max:500']]);
        $service->request($college, $studentEnrollment, $courseOffering, $data, $request->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Attendance exception request submitted for decision.']);
    }

    public function decide(Request $request, College $college, AttendanceExceptionRequest $attendanceExceptionRequest, AttendanceEligibilityService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_attendance_exception.decide');
        $data = $request->validate(['decision' => ['required', 'in:APPROVED,REJECTED'], 'remarks' => ['required', 'string', 'min:5', 'max:4000']]);
        $service->decide($college, $attendanceExceptionRequest, $data['decision'], $data['remarks'], $request->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Attendance exception decision recorded.']);
    }

    public function finalize(Request $request, College $college, StudentEnrollment $studentEnrollment, CourseOffering $courseOffering, AttendanceEligibilityService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_attendance_eligibility.finalize');
        $service->finalize($college, $studentEnrollment, $courseOffering, $request->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Final attendance examination eligibility recorded.']);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
