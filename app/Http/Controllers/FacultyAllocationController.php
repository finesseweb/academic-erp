<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\CourseOffering;
use App\Models\FacultyAllocation;
use App\Models\User;
use App\Services\FacultyAllocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FacultyAllocationController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        abort_unless($request->user()->hasCollegePermission('college_faculty_allocation.view', $college->id), 403);
        $courseOfferings = CourseOffering::with([
            'batch:id,college_program_offering_id,code,name,status', 'batch.sections:id,batch_id,code,name,status',
            'batch.offering:id,college_id,program_template_id,academic_session_id,status',
            'batch.offering.programTemplate:id,name,code', 'batch.offering.academicSession:id,name,code,is_current',
            'curriculumCourseMapping:id,curriculum_slot_id,course_id,discipline_id,specialization_id',
            'curriculumCourseMapping.course:id,code,name', 'curriculumCourseMapping.slot:id,curriculum_term_id,name',
            'curriculumCourseMapping.slot.term:id,name,sequence_no', 'curriculumCourseMapping.discipline:id,name,code',
            'curriculumCourseMapping.specialization:id,name,code',
        ])->whereHas('batch.offering', fn ($q) => $q->where('college_id', $college->id))->orderByDesc('id')->get();

        $faculty = User::query()->where('primary_college_id', $college->id)->where('account_type', 'COLLEGE_STAFF')->where('status', 'ACTIVE')
            ->whereHas('roles', fn ($q) => $q->where('roles.status', 'ACTIVE')->where('user_roles.status', 'ACTIVE')->where('user_roles.scope_type', 'COLLEGE')
                ->where('user_roles.scope_reference', "college:{$college->id}")->where(fn ($r) => $r->whereNull('user_roles.effective_from')->orWhere('user_roles.effective_from', '<=', now()))
                ->where(fn ($r) => $r->whereNull('user_roles.effective_until')->orWhere('user_roles.effective_until', '>=', now()))
                ->whereHas('permissions', fn ($permission) => $permission->where('permissions.code', 'college_faculty_allocation.eligible')->where('permissions.status', 'ACTIVE')))
            ->with(['roles' => fn ($q) => $q->select('roles.id', 'roles.name', 'roles.code')->wherePivot('scope_type', 'COLLEGE')->wherePivot('scope_reference', "college:{$college->id}")->wherePivot('status', 'ACTIVE')])
            ->orderBy('name')->get(['id', 'name', 'email']);

        $allocations = FacultyAllocation::with(['faculty:id,name,email', 'section:id,batch_id,name,code', 'courseOffering'])->whereHas('courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))->orderByDesc('id')->get();

        return Inertia::render('faculty-allocations/index', [
            'college' => $college->only(['id', 'name', 'code', 'status']), 'courseOfferings' => $courseOfferings, 'faculty' => $faculty, 'allocations' => $allocations,
            'can' => ['create' => $request->user()->hasCollegePermission('college_faculty_allocation.create', $college->id),
                'update' => $request->user()->hasCollegePermission('college_faculty_allocation.update', $college->id),
                'enable' => $request->user()->hasCollegePermission('college_faculty_allocation.enable', $college->id),
                'disable' => $request->user()->hasCollegePermission('college_faculty_allocation.disable', $college->id)],
        ]);
    }

    public function store(Request $request, College $college, FacultyAllocationService $service): RedirectResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_faculty_allocation.create', $college->id), 403);
        $service->save($college, $this->validated($request), $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Faculty allocation created as INACTIVE.']);
    }

    public function update(Request $request, College $college, FacultyAllocation $facultyAllocation, FacultyAllocationService $service): RedirectResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_faculty_allocation.update', $college->id), 403);
        $this->assertOwned($college, $facultyAllocation);
        $service->save($college, $this->validated($request), $request->user()->id, $request->ip(), $facultyAllocation);

        return back()->with('toast', ['type' => 'success', 'message' => 'Faculty allocation updated.']);
    }

    public function status(Request $request, College $college, FacultyAllocation $facultyAllocation, FacultyAllocationService $service): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]]);
        abort_unless($request->user()->hasCollegePermission($data['status'] === 'ACTIVE' ? 'college_faculty_allocation.enable' : 'college_faculty_allocation.disable', $college->id), 403);
        $this->assertOwned($college, $facultyAllocation);
        $service->changeStatus($college, $facultyAllocation, $data['status'], $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Faculty allocation status updated.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate(['course_offering_id' => ['required', 'integer'], 'delivery_scope' => ['required', Rule::in(['BATCH', 'SECTION'])], 'section_id' => ['nullable', 'integer', 'required_if:delivery_scope,SECTION'], 'faculty_user_id' => ['required', 'integer'],
            'teaching_role' => ['required', Rule::in(['PRIMARY', 'CO_FACULTY', 'PRACTICAL'])], 'weekly_load' => ['nullable', 'numeric', 'min:0.25', 'max:168'], 'notes' => ['nullable', 'string', 'max:2000']]);
    }

    private function assertOwned(College $college, FacultyAllocation $row): void
    {
        abort_unless(FacultyAllocation::whereKey($row->id)->whereHas('courseOffering.batch.offering', fn ($q) => $q->where('college_id', $college->id))->exists(), 404);
    }
}
