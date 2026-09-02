<?php

namespace App\Http\Controllers;

use App\Models\College;
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
        $search = $request->validate(['search' => ['nullable', 'string', 'max:100']])['search'] ?? null;
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

        return Inertia::render('college-users/index', ['college' => $college->only(['id', 'name', 'code']), 'users' => $users, 'filters' => ['search' => $search], 'can' => ['create' => $request->user()->hasCollegePermission('college_user.create', $college->id), 'update' => $request->user()->hasCollegePermission('college_user.update', $college->id), 'disable' => $request->user()->hasCollegePermission('college_user.disable', $college->id), 'enable' => $request->user()->hasCollegePermission('college_user.enable', $college->id), 'resetPassword' => $request->user()->hasCollegePermission('college_user.reset_password', $college->id), 'manageRoles' => $request->user()->hasCollegePermission('college_role.view', $college->id)]]);
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
        $service->create([...$data, 'account_type' => 'COLLEGE_STAFF', 'primary_college_id' => $college->id, 'status' => 'ACTIVE'], $request->user()->id, $request->ip());

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

    private function assertOwned(College $college, User $user): void
    {
        abort_unless($user->primary_college_id === $college->id && $user->account_type === 'COLLEGE_STAFF', 404);
    }
}
