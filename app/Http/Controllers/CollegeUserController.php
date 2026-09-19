<?php

namespace App\Http\Controllers;

use App\Models\AcademicDiscipline;
use App\Models\AcademicSession;
use App\Models\College;
use App\Models\CollegeProgramOffering;
use App\Models\Student;
use App\Models\User;
use App\Services\UserAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class CollegeUserController extends Controller
{
    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }

    public function index(Request $request, College $college): Response
    {
        $this->authorizeCollege($request, $college, 'college_user.view');
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', Rule::in(['staff', 'students'])],
            'session_id' => ['nullable', 'integer'],
            'offering_id' => ['nullable', 'integer'],
            'discipline_id' => ['nullable', 'integer'],
        ]);
        $search = $filters['search'] ?? null;
        $type = $filters['type'] ?? 'staff';
        $sessions = collect();
        $offerings = collect();
        $disciplines = collect();
        $sessionId = 0;
        $offeringId = 0;
        $disciplineId = 0;

        if ($type === 'students') {
            $sessions = AcademicSession::query()
                ->where('university_id', $college->university_id)
                ->where('status', 'ACTIVE')
                ->orderByDesc('is_current')->orderByDesc('starts_on')
                ->get(['id', 'name', 'code', 'is_current']);
            $sessionId = (int) ($filters['session_id'] ?? ($sessions->firstWhere('is_current', true)?->id ?? $sessions->first()?->id ?? 0));
            if ($sessionId && ! $sessions->contains('id', $sessionId)) $sessionId = 0;

            $offerings = CollegeProgramOffering::query()->with('programTemplate:id,name,code')
                ->where('college_id', $college->id)
                ->when($sessionId, fn ($q) => $q->where('academic_session_id', $sessionId))
                ->orderBy('program_template_id')->get(['id', 'program_template_id', 'academic_session_id'])
                ->map(fn ($o) => ['id' => (int) $o->id, 'name' => $o->programTemplate?->name, 'code' => $o->programTemplate?->code]);
            $offeringId = (int) ($filters['offering_id'] ?? 0);
            if ($offeringId && ! $offerings->contains('id', $offeringId)) $offeringId = 0;

            $disciplineIds = \App\Models\StudentEnrollment::query()
                ->where('college_id', $college->id)->where('status', 'ENROLLED')->whereNotNull('discipline_id')
                ->when($sessionId, fn ($q) => $q->whereHas('offering', fn ($o) => $o->where('academic_session_id', $sessionId)))
                ->when($offeringId, fn ($q) => $q->where('college_program_offering_id', $offeringId))
                ->distinct()->pluck('discipline_id');
            $disciplines = AcademicDiscipline::query()->whereIn('id', $disciplineIds)->orderBy('name')->get(['id', 'name', 'code']);
            $disciplineId = (int) ($filters['discipline_id'] ?? 0);
            if ($disciplineId && ! $disciplines->contains('id', $disciplineId)) $disciplineId = 0;
            // Student account visibility is derived from the canonical Student -> User
            // relationship. Do not filter on users.account_type: Admission-origin students
            // intentionally retain their Applicant identity while Import-origin students
            // use STUDENT accounts.
            $users = Student::query()
                ->where('college_id', $college->id)
                ->whereNotNull('user_id')
                ->whereHas('user')
                ->whereHas('enrollments', fn ($e) => $e->where('college_id', $college->id)->where('status', 'ENROLLED')
                    ->when($sessionId, fn ($q) => $q->whereHas('offering', fn ($o) => $o->where('academic_session_id', $sessionId)))
                    ->when($offeringId, fn ($q) => $q->where('college_program_offering_id', $offeringId))
                    ->when($disciplineId, fn ($q) => $q->where('discipline_id', $disciplineId)))
                ->with(['user:id,name,email,mobile,status,must_change_password', 'enrollments' => fn ($q) => $q
                    ->where('college_id', $college->id)
                    ->where('status', 'ENROLLED')
                    ->when($sessionId, fn ($x) => $x->whereHas('offering', fn ($o) => $o->where('academic_session_id', $sessionId)))
                    ->when($offeringId, fn ($x) => $x->where('college_program_offering_id', $offeringId))
                    ->when($disciplineId, fn ($x) => $x->where('discipline_id', $disciplineId))
                    ->latest('id')])
                ->when($search, fn ($q, $v) => $q->where(fn ($i) => $i
                    ->where('full_name', 'like', "%{$v}%")
                    ->orWhere('email', 'like', "%{$v}%")
                    ->orWhere('student_uid', 'like', "%{$v}%")
                    ->orWhere('university_roll_no', 'like', "%{$v}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%"))
                    ->orWhereHas('enrollments', fn ($e) => $e->where('college_id', $college->id)->where('class_roll_no', 'like', "%{$v}%"))))
                ->orderBy('full_name')
                ->paginate(15)
                ->withQueryString()
                ->through(fn (Student $student) => [
                    'id' => $student->user->id,
                    'name' => $student->user->name ?: $student->full_name,
                    'email' => $student->user->email,
                    'mobile' => $student->user->mobile,
                    'status' => $student->user->status,
                    'student' => [
                        'id' => $student->id,
                        'student_uid' => $student->student_uid,
                        'university_roll_no' => $student->university_roll_no,
                        'class_roll_no' => $student->enrollments->first()?->class_roll_no,
                        'source_type' => $student->source_type,
                    ],
                ]);
        } else {
            $users = User::query()
                ->where('primary_college_id', $college->id)
                ->where('account_type', 'COLLEGE_STAFF')
                ->with(['roles' => fn ($q) => $q
                    ->select('roles.id', 'roles.name', 'roles.code')
                    ->where('roles.status', 'ACTIVE')
                    ->wherePivot('status', 'ACTIVE')
                    ->wherePivot('scope_type', 'COLLEGE')
                    ->wherePivot('scope_reference', "college:{$college->id}")])
                ->when($search, fn ($q, $v) => $q->where(fn ($i) => $i->where('name', 'like', "%{$v}%")->orWhere('email', 'like', "%{$v}%")))
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
        }

        return Inertia::render('college-users/index', ['college' => $college->only(['id', 'name', 'code']), 'users' => $users, 'sessions' => $sessions, 'offerings' => $offerings, 'disciplines' => $disciplines, 'filters' => ['search' => $search, 'type' => $type, 'session_id' => $sessionId, 'offering_id' => $offeringId, 'discipline_id' => $disciplineId], 'can' => ['create' => $request->user()->hasCollegePermission('college_user.create', $college->id), 'update' => $request->user()->hasCollegePermission('college_user.update', $college->id), 'disable' => $request->user()->hasCollegePermission('college_user.disable', $college->id), 'enable' => $request->user()->hasCollegePermission('college_user.enable', $college->id), 'resetPassword' => $request->user()->hasCollegePermission('college_user.reset_password', $college->id), 'manageRoles' => $request->user()->hasCollegePermission('college_role.view', $college->id), 'viewStudentProfile' => $request->user()->hasCollegePermission('college_student_profile.view', $college->id)]]);
    }

    public function create(Request $request, College $college): Response
    {
        $this->authorizeCollege($request, $college, 'college_user.create');

        return Inertia::render('college-users/create', ['college' => $college->only(['id', 'name', 'code'])]);
    }

    public function store(Request $request, College $college, UserAdministrationService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_user.create');
        $request->merge([
            'name' => is_string($request->input('name')) ? trim($request->input('name')) : $request->input('name'),
            'email' => is_string($request->input('email')) ? mb_strtolower(trim($request->input('email'))) : $request->input('email'),
            'mobile' => is_string($request->input('mobile')) ? trim($request->input('mobile')) : $request->input('mobile'),
        ]);
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email:rfc', Rule::unique('users', 'email')], 'mobile' => ['nullable', 'string', 'max:30'], 'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()]],
            ['email.unique' => 'A user account with this email already exists. Duplicate users cannot be created.'],
        );
        $creatorScopeType = $request->user()->primary_college_id === null ? 'UNIVERSITY' : 'COLLEGE';
        $service->create([...$data, 'account_type' => 'COLLEGE_STAFF', 'primary_college_id' => $college->id, 'status' => 'ACTIVE'], $request->user()->id, $request->ip(), $creatorScopeType);

        return to_route('college-users.index', $college)->with('toast', ['type' => 'success', 'message' => 'College user created.']);
    }

    public function status(Request $request, College $college, User $user, UserAdministrationService $service): RedirectResponse
    {
        $this->assertOwned($college, $user);
        $status = $request->validate(['status' => ['required', 'in:ACTIVE,INACTIVE']])['status'];
        $this->authorizeCollege($request, $college, $status === 'ACTIVE' ? 'college_user.enable' : 'college_user.disable');
        abort_if($request->user()->is($user), 422, 'You cannot disable your own account.');
        $service->status($user, $status, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'College user status updated.']);
    }

    public function edit(Request $request, College $college, User $user): Response
    {
        $this->authorizeCollege($request, $college, 'college_user.update');
        $this->assertOwned($college, $user);

        return Inertia::render('college-users/edit', ['college' => $college->only(['id', 'name', 'code']), 'managedUser' => $user->only(['id', 'name', 'email', 'mobile', 'status'])]);
    }

    public function update(Request $request, College $college, User $user, UserAdministrationService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_user.update');
        $this->assertOwned($college, $user);
        $request->merge([
            'name' => is_string($request->input('name')) ? trim($request->input('name')) : $request->input('name'),
            'email' => is_string($request->input('email')) ? mb_strtolower(trim($request->input('email'))) : $request->input('email'),
            'mobile' => is_string($request->input('mobile')) ? trim($request->input('mobile')) : $request->input('mobile'),
        ]);
        $data = $request->validate(
            ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'email:rfc', Rule::unique('users', 'email')->ignore($user)], 'mobile' => ['nullable', 'string', 'max:30']],
            ['email.unique' => 'Another user account already uses this email.'],
        );
        $service->update($user, $data, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'College user updated.']);
    }

    public function resetPassword(Request $request, College $college, User $user, UserAdministrationService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_user.reset_password');
        $this->assertOwned($college, $user);
        $status = PasswordBroker::sendResetLink(['email' => $user->email]);
        if ($status !== PasswordBroker::RESET_LINK_SENT) {
            return back()->with('toast', ['type' => 'error', 'message' => __($status)]);
        }
        $service->auditPasswordReset($user, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Password reset link sent.']);
    }


    public function studentStatus(Request $request, College $college, Student $student, UserAdministrationService $service): RedirectResponse
    {
        $this->assertStudentOwned($college, $student);
        $user = $student->user()->firstOrFail();
        $status = $request->validate(['status' => ['required', 'in:ACTIVE,INACTIVE']])['status'];
        $this->authorizeCollege($request, $college, $status === 'ACTIVE' ? 'college_user.enable' : 'college_user.disable');
        abort_if($request->user()->is($user), 422, 'You cannot disable your own account.');
        $service->status($user, $status, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Student login access updated.']);
    }

    public function studentResetPassword(Request $request, College $college, Student $student, UserAdministrationService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_user.reset_password');
        $this->assertStudentOwned($college, $student);
        $user = $student->user()->firstOrFail();
        $status = PasswordBroker::sendResetLink(['email' => $user->email]);
        if ($status !== PasswordBroker::RESET_LINK_SENT) {
            return back()->with('toast', ['type' => 'error', 'message' => __($status)]);
        }
        $service->auditPasswordReset($user, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Student password reset link sent.']);
    }

    private function assertStudentOwned(College $college, Student $student): void
    {
        abort_unless((int) $student->college_id === (int) $college->id && $student->user_id !== null, 404);
    }

    private function assertOwned(College $college, User $user): void
    {
        abort_unless($user->primary_college_id === $college->id && $user->account_type === 'COLLEGE_STAFF', 404);
    }
}
