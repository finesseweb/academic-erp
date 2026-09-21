<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseOfferingRequest;
use App\Models\Batch;
use App\Models\College;
use App\Models\CourseOffering;
use App\Services\CourseOfferingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseOfferingController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        $this->authorizeCollege($request, $college, 'college_course_offering.view');

        $batches = Batch::query()->with([
            'offering:id,college_id,program_template_id,curriculum_id,academic_session_id,status',
            'offering.programTemplate:id,name,code', 'offering.curriculum:id,name,code,version',
            'offering.academicSession:id,name,code,status,is_current',
        ])->whereHas('offering', fn ($q) => $q->where('college_id', $college->id))
          ->orderByDesc('id')->get();

        $curriculumIds = $batches->pluck('offering.curriculum_id')->filter()->unique();
        $mappings = DB::table('curriculum_course_mappings as m')
            ->join('curriculum_slots as s', 's.id', '=', 'm.curriculum_slot_id')
            ->join('curriculum_terms as t', 't.id', '=', 's.curriculum_term_id')
            ->join('courses as c', 'c.id', '=', 'm.course_id')
            ->whereIn('t.curriculum_id', $curriculumIds)
            ->orderBy('t.sequence_no')->orderBy('s.display_order')->orderBy('m.display_order')->orderBy('c.name')
            ->leftJoin('academic_disciplines as d', 'd.id', '=', 'm.discipline_id')
            ->leftJoin('academic_disciplines as sp', 'sp.id', '=', 'm.specialization_id')
            ->get(['m.id','m.status','m.discipline_id','m.specialization_id','t.curriculum_id','t.id as term_id','t.name as term_name','t.sequence_no','t.status as term_status',
                's.id as slot_id','s.name as slot_name','s.status as slot_status','s.credits','s.credit_counting','s.selection_mode','s.min_selection','s.max_selection',
                'c.id as course_id','c.code as course_code','c.name as course_name','d.name as discipline_name','d.code as discipline_code',
                'sp.name as specialization_name','sp.code as specialization_code']);


        $offerings = CourseOffering::query()->with([
            'batch:id,college_program_offering_id,code,name,status',
            'batch.offering:id,college_id,program_template_id,curriculum_id,academic_session_id,status',
            'batch.offering.programTemplate:id,name,code', 'batch.offering.academicSession:id,name,code,is_current',
        ])->whereHas('batch.offering', fn ($q) => $q->where('college_id', $college->id))->orderByDesc('id')->get();

        $mappingById = $mappings->keyBy('id');
        $offerings->each(fn ($offering) => $offering->setAttribute('mapping_context', $mappingById->get($offering->curriculum_course_mapping_id)));

        return Inertia::render('course-offerings/index', [
            'college' => $college->only(['id','name','code','status']),
            'batches' => $batches,
            'mappings' => $mappings,
            'offerings' => $offerings,
            'can' => [
                'create' => $request->user()->hasCollegePermission('college_course_offering.create', $college->id),
                'enable' => $request->user()->hasCollegePermission('college_course_offering.enable', $college->id),
                'disable' => $request->user()->hasCollegePermission('college_course_offering.disable', $college->id),
            ],
        ]);
    }

    public function store(StoreCourseOfferingRequest $request, College $college, CourseOfferingService $service): RedirectResponse
    {
        $created = $service->createForDisciplineTerm($college, $request->validated(), $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => $created->count().' Course Offering(s) created as INACTIVE from the University Curriculum.']);
    }

    public function status(Request $request, College $college, CourseOffering $courseOffering, CourseOfferingService $service): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', Rule::in(['ACTIVE','INACTIVE'])]])['status'];
        $permission = $status === 'ACTIVE' ? 'college_course_offering.enable' : 'college_course_offering.disable';
        $this->authorizeCollege($request, $college, $permission);
        $service->changeStatus($courseOffering, $college, $status, $request->user()->id, $request->ip());
        return back()->with('toast', ['type' => 'success', 'message' => $status === 'ACTIVE' ? 'Course Offering activated.' : 'Course Offering deactivated.']);
    }

    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }
}
