<?php

namespace App\Http\Controllers;

use App\Models\AcademicDiscipline;
use App\Models\AcademicSession;
use App\Models\Admission;
use App\Models\College;
use App\Models\CollegeAdmissionApplicationAcademicPreference;
use App\Models\CollegeProgramOffering;
use App\Models\StudentEnrollment;
use App\Models\StudentIdentitySetting;
use App\Services\StudentEnrollmentService;
use App\Services\StudentIdentityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeStudentIdentityController extends Controller
{
    public function index(Request $request, College $college): Response
    {
        abort_unless($request->user()->hasCollegePermission('college_student_identity.view', $college->id), 403);
        $sessions = AcademicSession::query()->where('university_id', $college->university_id)->where('status', 'ACTIVE')->orderByDesc('is_current')->orderByDesc('starts_on')->get(['id', 'name', 'code', 'is_current']);
        $sessionId = (int) $request->query('session_id', (int) ($sessions->firstWhere('is_current', true)?->id ?? $sessions->first()?->id ?? 0));
        $offerings = CollegeProgramOffering::query()->with(['programTemplate:id,name,code', 'batches' => fn ($q) => $q->where('status', 'ACTIVE')->orderBy('code'), 'batches.sections' => fn ($q) => $q->where('status', 'ACTIVE')->orderBy('code')])->where('college_id', $college->id)->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))->orderBy('program_template_id')->get(['id', 'program_template_id', 'academic_session_id'])->map(fn ($o) => ['id' => (int) $o->id, 'name' => $o->programTemplate?->name, 'code' => $o->programTemplate?->code, 'batches' => $o->batches->map(fn ($b) => ['id' => (int) $b->id, 'code' => $b->code, 'name' => $b->name, 'sections' => $b->sections->map(fn ($sec) => ['id' => (int) $sec->id, 'code' => $sec->code, 'name' => $sec->name])->values()])->values()]);
        $offeringId = (int) $request->query('offering_id', 0);
        if ($offeringId && ! $offerings->contains('id', $offeringId)) {
            $offeringId = 0;
        }
        $disciplineIds = StudentEnrollment::query()->where('college_id', $college->id)->where('status', 'ENROLLED')
            ->when($sessionId, fn ($x) => $x->whereHas('offering', fn ($o) => $o->where('academic_session_id', $sessionId)))
            ->when($offeringId, fn ($x) => $x->where('college_program_offering_id', $offeringId))
            ->whereNotNull('discipline_id')->pluck('discipline_id');
        $legacyDisciplineIds = CollegeAdmissionApplicationAcademicPreference::query()->whereNotNull('discipline_id')
            ->whereIn('college_admission_application_id', Admission::query()->select('college_admission_application_id')->where('college_id', $college->id)->whereIn('id', StudentEnrollment::query()->select('admission_id')->where('college_id', $college->id)->where('status', 'ENROLLED')))
            ->when($sessionId, fn ($x) => $x->whereHas('offering', fn ($o) => $o->where('academic_session_id', $sessionId)))
            ->when($offeringId, fn ($x) => $x->where('college_program_offering_id', $offeringId))->pluck('discipline_id');
        $disciplineOptions = AcademicDiscipline::query()->whereIn('id', $disciplineIds->merge($legacyDisciplineIds)->unique())->orderBy('name')->get(['id', 'name', 'code'])->map(fn ($d) => ['id' => (int) $d->id, 'name' => $d->name, 'code' => $d->code])->values();
        $disciplineId = (int) $request->query('discipline_id', 0);
        if ($disciplineId && ! $disciplineOptions->contains('id', $disciplineId)) {
            $disciplineId = 0;
        }
        $q = trim((string) $request->query('q', ''));
        $per = (int) $request->query('per_page', 25);
        if (! in_array($per, [25, 50, 100], true)) {
            $per = 25;
        }
        $rows = StudentEnrollment::query()->with(['student:id,full_name,student_uid,university_roll_no', 'offering.programTemplate:id,name,code', 'offering.academicSession:id,name,code', 'discipline:id,name,code', 'admission.application.academicPreference.discipline:id,name,code'])
            ->where('college_id', $college->id)->where('status', 'ENROLLED')->when($sessionId, fn ($x) => $x->whereHas('offering', fn ($o) => $o->where('academic_session_id', $sessionId)))->when($offeringId, fn ($x) => $x->where('college_program_offering_id', $offeringId))
            ->when($disciplineId, fn ($x) => $x->where(function ($q) use ($disciplineId) {
                $q->where('discipline_id', $disciplineId)->orWhere(function ($legacy) use ($disciplineId) {
                    $legacy->whereNull('discipline_id')->whereHas('admission.application.academicPreference', fn ($p) => $p->where('discipline_id', $disciplineId));
                });
            }))
            ->when($q !== '', fn ($x) => $x->whereHas('student', fn ($s) => $s->where('full_name', 'like', '%'.$q.'%')->orWhere('student_uid', 'like', '%'.$q.'%')->orWhere('university_roll_no', 'like', '%'.$q.'%')))
            ->orderByDesc('id')->paginate($per)->withQueryString()->through(fn ($e) => ['id' => (int) $e->id, 'student_id' => (int) $e->student_id, 'name' => $e->student?->full_name, 'student_uid' => $e->student?->student_uid, 'university_roll_no' => $e->student?->university_roll_no, 'class_roll_no' => $e->class_roll_no, 'programme' => $e->offering?->programTemplate?->name, 'discipline' => $e->discipline?->name ?? $e->admission?->application?->academicPreference?->discipline?->name, 'discipline_code' => $e->discipline?->code ?? $e->admission?->application?->academicPreference?->discipline?->code, 'session' => $e->offering?->academicSession?->name, 'offering_id' => (int) $e->college_program_offering_id, 'batch_id' => $e->batch_id, 'section_id' => $e->section_id, 'placement_complete' => (bool) ($e->batch_id && $e->section_id), 'complete' => (bool) ($e->student?->student_uid && $e->student?->university_roll_no && $e->class_roll_no)]);

        return Inertia::render('college-student-identities/index', ['college' => $college->only(['id', 'name', 'code']), 'sessions' => $sessions, 'offerings' => $offerings, 'disciplines' => $disciplineOptions, 'students' => $rows, 'settings' => StudentIdentitySetting::forCollege($college->id)->only(['student_uid_format', 'university_roll_format', 'class_roll_format', 'class_roll_scope']), 'filters' => ['session_id' => $sessionId, 'offering_id' => $offeringId, 'discipline_id' => $disciplineId, 'q' => $q, 'per_page' => $per], 'can' => ['manage' => $request->user()->hasCollegePermission('college_student_identity.manage', $college->id)]]);
    }

    public function updateSettings(Request $request, College $college): RedirectResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_student_identity.manage', $college->id), 403);
        $data = $request->validate([
            'student_uid_format' => ['required', 'string', 'max:160', 'regex:/\{SEQ(?::\d{1,2})?\}/'],
            'university_roll_format' => ['required', 'string', 'max:160', 'regex:/\{SEQ(?::\d{1,2})?\}/'],
            'class_roll_format' => ['required', 'string', 'max:160', 'regex:/\{SEQ(?::\d{1,2})?\}/'],
            'class_roll_scope' => ['required', Rule::in(['PROGRAMME_OFFERING', 'DISCIPLINE'])],
        ]);
        $setting = StudentIdentitySetting::forCollege($college->id);
        $before = $setting->only(['student_uid_format', 'university_roll_format', 'class_roll_format', 'class_roll_scope']);
        $setting->fill($data);
        $setting->updated_by = $request->user()->id;
        $setting->save();
        $after = $setting->fresh()->only(['student_uid_format', 'university_roll_format', 'class_roll_format', 'class_roll_scope']);
        if ($before !== $after) {
            DB::table('audit_logs')->insert([
                'actor_user_id' => $request->user()->id,
                'event' => 'student.identity.rules.updated',
                'resource_type' => 'student_identity_setting',
                'resource_id' => $setting->id,
                'scope_type' => 'COLLEGE',
                'scope_reference' => 'college:'.$college->id,
                'before' => json_encode($before),
                'after' => json_encode($after),
                'ip_address' => $request->ip(),
                'created_at' => now(),
            ]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Student identity rules saved. Existing assigned identities are unchanged.']);
    }

    public function assign(Request $request, College $college, StudentEnrollment $enrollment, StudentIdentityService $service): RedirectResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_student_identity.manage', $college->id), 403);
        abort_unless((int) $enrollment->college_id === (int) $college->id, 404);
        $service->assign($enrollment, (int) $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Student identities assigned successfully.']);
    }

    public function assignPlacement(Request $request, College $college, StudentEnrollmentService $service): RedirectResponse
    {
        abort_unless($request->user()->hasCollegePermission('college_student_identity.manage', $college->id), 403);
        $data = $request->validate([
            'enrollment_ids' => ['required', 'array', 'min:1', 'max:500'],
            'enrollment_ids.*' => ['required', 'integer', 'distinct'],
            'batch_id' => ['required', 'integer'],
            'section_id' => ['required', 'integer'],
        ]);
        $changed = $service->assignPlacements($college, $data['enrollment_ids'], (int) $data['batch_id'], (int) $data['section_id'], (int) $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => $changed ? "Academic placement assigned to {$changed} student(s)." : 'Selected students already have this placement.']);
    }
}
