<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\CourseOffering;
use App\Models\FacultyAllocation;
use App\Models\InternalAssessmentActivity;
use App\Models\InternalAssessmentComponent;
use App\Services\InternalAssessmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class InternalAssessmentController extends Controller
{
    public function index(Request $request, College $college, string $mode = 'SETUP'): Response
    {
        $this->authorizeCollege($request, $college, 'college_internal_assessment.view');
        $offerings = CourseOffering::with(['batch.offering.academicSession:id,name,code,is_current', 'batch:id,college_program_offering_id,name,code', 'curriculumCourseMapping.course:id,name,code', 'curriculumCourseMapping.slot.term:id,name,sequence_no'])->whereHas('batch.offering', fn ($q) => $q->where('college_id', $college->id))->orderByDesc('id')->get();
        $components = InternalAssessmentComponent::with(['courseOffering.batch:id,name,code', 'courseOffering.curriculumCourseMapping.course:id,name,code', 'academicPolicy:id,name,code,version'])->whereHas('courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))->orderBy('display_order')->get();
        $allocations = FacultyAllocation::with('faculty:id,name,email')->where('status', 'ACTIVE')->whereHas('courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))->get();
        $activities = InternalAssessmentActivity::with(['component.courseOffering.curriculumCourseMapping.course:id,name,code', 'facultyAllocation.faculty:id,name'])->whereHas('component.courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))->when($mode !== 'SETUP', fn ($q) => $q->whereHas('component', fn ($c) => $c->where('component_type', $mode)))->latest()->get();

        return Inertia::render('internal-assessment/index', ['college' => $college->only(['id', 'name', 'code']), 'mode' => $mode, 'offerings' => $offerings, 'components' => $components, 'allocations' => $allocations, 'activities' => $activities, 'can' => ['setup' => $request->user()->hasCollegePermission('college_internal_assessment.setup', $college->id), 'assignment' => $request->user()->hasCollegePermission('college_internal_assessment.assignment', $college->id), 'quiz' => $request->user()->hasCollegePermission('college_internal_assessment.quiz', $college->id), 'mid_semester' => $request->user()->hasCollegePermission('college_internal_assessment.mid_semester', $college->id), 'practical' => $request->user()->hasCollegePermission('college_internal_assessment.practical', $college->id)]]);
    }

    public function setup(Request $request, College $college): Response
    {
        return $this->index($request, $college, 'SETUP');
    }

    public function assignments(Request $request, College $college): Response
    {
        return $this->index($request, $college, 'ASSIGNMENT');
    }

    public function quizzes(Request $request, College $college): Response
    {
        return $this->index($request, $college, 'QUIZ');
    }

    public function midSemesters(Request $request, College $college): Response
    {
        return $this->index($request, $college, 'MID_SEMESTER');
    }

    public function practicals(Request $request, College $college): Response
    {
        return $this->index($request, $college, 'PRACTICAL');
    }

    public function marks(Request $request, College $college): Response
    {
        $this->authorizeCollege($request, $college, 'college_internal_assessment.view');
        $activities = InternalAssessmentActivity::with(['component.courseOffering.curriculumCourseMapping.course:id,name,code', 'component.courseOffering.batch:id,name,code', 'facultyAllocation.faculty:id,name'])->whereIn('status', ['PUBLISHED', 'CLOSED'])->whereHas('component.courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))->latest()->get();
        $selected = $activities->firstWhere('id', $request->integer('activity_id')) ?? $activities->first();
        $students = $selected ? $selected->students()->with(['enrollment.student:id,full_name,student_uid,university_roll_no', 'mark'])->orderBy('id')->get() : collect();

        return Inertia::render('internal-assessment/marks', ['college' => $college->only(['id', 'name', 'code']), 'activities' => $activities, 'selectedActivityId' => $selected?->id, 'students' => $students, 'canEnter' => $request->user()->hasCollegePermission('college_internal_assessment.marks_entry', $college->id)]);
    }

    public function storeComponent(Request $request, College $college, InternalAssessmentService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_internal_assessment.setup');
        $service->saveComponent($college, $this->componentData($request), $request->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Assessment component created as DRAFT.']);
    }

    public function updateComponent(Request $request, College $college, InternalAssessmentComponent $component, InternalAssessmentService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_internal_assessment.setup');
        $service->saveComponent($college, $this->componentData($request), $request->user()->id, $component);

        return back()->with('toast', ['type' => 'success', 'message' => 'Assessment component updated.']);
    }

    public function componentStatus(Request $request, College $college, InternalAssessmentComponent $component, InternalAssessmentService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_internal_assessment.setup');
        $d = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]]);
        $service->componentStatus($college, $component, $d['status'], $request->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Assessment component status updated.']);
    }

    public function storeActivity(Request $request, College $college, string $type, InternalAssessmentService $service): RedirectResponse
    {
        $type = strtoupper($type);
        abort_unless(in_array($type, ['ASSIGNMENT', 'QUIZ', 'MID_SEMESTER', 'PRACTICAL'], true), 404);
        $this->authorizeCollege($request, $college, $this->activityPermission($type));
        $service->saveActivity($college, $type, $this->activityData($request), $request->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => ucfirst(strtolower($type)).' created as DRAFT.']);
    }

    public function activityStatus(Request $request, College $college, InternalAssessmentActivity $activity, InternalAssessmentService $service): RedirectResponse
    {
        $activity->loadMissing('component');
        $permission = $this->activityPermission($activity->component->component_type);
        $this->authorizeCollege($request, $college, $permission);
        $d = $request->validate(['status' => ['required', Rule::in(['PUBLISHED', 'CLOSED'])]]);
        $service->activityStatus($college, $activity, $d['status'], $request->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Assessment activity status updated.']);
    }

    public function saveMarks(Request $request, College $college, InternalAssessmentActivity $activity, InternalAssessmentService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_internal_assessment.marks_entry');
        $data = $request->validate(['records' => ['required', 'array', 'min:1'], 'records.*.internal_assessment_activity_student_id' => ['required', 'integer'], 'records.*.result_status' => ['required', Rule::in(['ENTERED', 'ABSENT'])], 'records.*.marks_obtained' => ['nullable', 'numeric', 'min:0'], 'records.*.remarks' => ['nullable', 'string', 'max:500']]);
        $service->saveMarks($college, $activity, $data['records'], $request->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Internal Assessment marks saved with audit history.']);
    }

    /** @return array<string, mixed> */
    private function componentData(Request $r): array
    {
        return $r->validate(['course_offering_id' => ['required', 'integer'], 'component_type' => ['required', Rule::in(['ASSIGNMENT', 'QUIZ', 'MID_SEMESTER', 'PRACTICAL', 'OTHER'])], 'name' => ['required', 'string', 'max:150'], 'maximum_marks' => ['required', 'numeric', 'gt:0', 'max:999999'], 'weightage_percent' => ['required', 'numeric', 'gt:0', 'max:100'], 'minimum_pass_marks' => ['nullable', 'numeric', 'min:0'], 'display_order' => ['required', 'integer', 'min:1', 'max:999'], 'notes' => ['nullable', 'string', 'max:2000']]);
    }

    /** @return array<string, mixed> */
    private function activityData(Request $r): array
    {
        return $r->validate(['internal_assessment_component_id' => ['required', 'integer'], 'faculty_allocation_id' => ['required', 'integer'], 'title' => ['required', 'string', 'max:200'], 'instructions' => ['nullable', 'string', 'max:10000'], 'opens_at' => ['required', 'date'], 'closes_at' => ['required', 'date'], 'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:1440']]);
    }

    private function authorizeCollege(Request $r, College $c, string $p): void
    {
        abort_unless($r->user()->hasCollegePermission($p, $c->id), 403);
    }

    private function activityPermission(string $type): string
    {
        return match ($type) {
            'QUIZ' => 'college_internal_assessment.quiz',
            'MID_SEMESTER' => 'college_internal_assessment.mid_semester',
            'PRACTICAL' => 'college_internal_assessment.practical',
            default => 'college_internal_assessment.assignment',
        };
    }
}
