<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\College;
use App\Models\Role;
use App\Models\User;
use App\Services\UserAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('user.view'), 403);

        $request->merge([
            'status' => $request->input('status') === 'all' ? null : $request->input('status'),
            'account_type' => $request->input('account_type') === 'all' ? null : $request->input('account_type'),
            'role_id' => $request->input('role_id') === 'all' ? null : $request->input('role_id'),
            'college_id' => $request->input('college_id') === 'all' ? null : $request->input('college_id'),
        ]);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
            'account_type' => ['nullable', Rule::in(['SYSTEM_ADMIN', 'UNIVERSITY_STAFF', 'COLLEGE_STAFF', 'OTHER'])],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'college_id' => ['nullable', 'integer', 'exists:colleges,id'],
            'sort' => ['nullable', Rule::in(['name', 'email', 'status', 'last_login_at', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        // Access Management is for internal ERP identities. Applicant identities are
        // managed by the Admission workflow and must not be mixed into staff RBAC.
        $base = User::query()->visibleToUniversityAdministration()->where('account_type', '!=', 'APPLICANT');

        $query = (clone $base)->with([
            'primaryCollege:id,name,code,university_id',
            'roles' => fn ($q) => $q
                ->select('roles.id', 'roles.name', 'roles.is_system_role')
                ->where('roles.status', 'ACTIVE')
                ->wherePivot('status', 'ACTIVE'),
        ]);

        $query
            ->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($i) => $i
                ->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('mobile', 'like', "%{$s}%")))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['account_type'] ?? null, fn ($q, $v) => $q->where('account_type', $v))
            ->when($filters['college_id'] ?? null, fn ($q, $v) => $q->where('primary_college_id', $v))
            ->when($filters['role_id'] ?? null, fn ($q, $v) => $q->whereHas('roles', fn ($r) => $r
                ->where('roles.id', $v)
                ->where('roles.status', 'ACTIVE')
                ->where('user_roles.status', 'ACTIVE')));

        return Inertia::render('users/index', [
            'users' => $query->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')->paginate(15)->withQueryString(),
            'roles' => Role::query()->visibleToUniversityAdministration()->where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name']),
            'colleges' => College::query()->where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name', 'code']),
            'filters' => $filters,
            'summary' => [
                'total' => (clone $base)->count(),
                'active' => (clone $base)->where('status', 'ACTIVE')->count(),
                'inactive' => (clone $base)->where('status', 'INACTIVE')->count(),
            ],
            'can' => [
                'create' => $request->user()->hasPermission('user.create'),
                'update' => $request->user()->hasPermission('user.update'),
                'enable' => $request->user()->hasPermission('user.enable'),
                'disable' => $request->user()->hasPermission('user.disable'),
                'resetPassword' => $request->user()->hasPermission('user.reset_password'),
                'manageRoles' => $request->user()->hasPermission('role.view'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('user.create'), 403);

        return Inertia::render('users/create');
    }

    public function store(StoreUserRequest $request, UserAdministrationService $service): RedirectResponse
    {
        $user = $service->create($request->validated(), $request->user()->id, $request->ip());

        return to_route('users.edit', $user)->with('toast', ['type' => 'success', 'message' => 'User account created.']);
    }

    public function edit(Request $request, User $user): Response
    {
        abort_unless($request->user()->hasPermission('user.view') && $request->user()->hasPermission('user.update'), 403);
        $this->assertUniversityManaged($user);

        return Inertia::render('users/edit', ['managedUser' => $user->load('roles:id,name')]);
    }

    public function update(UpdateUserRequest $request, User $user, UserAdministrationService $service): RedirectResponse
    {
        $this->assertUniversityManaged($user);
        $service->update($user, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'User account updated.']);
    }

    public function status(Request $request, User $user, UserAdministrationService $service): RedirectResponse
    {
        $this->assertUniversityManaged($user);
        $data = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]]);
        abort_unless($request->user()->hasPermission($data['status'] === 'ACTIVE' ? 'user.enable' : 'user.disable'), 403);
        abort_if($request->user()->is($user) && $data['status'] === 'INACTIVE', 422, 'You cannot disable your own account.');
        abort_if($data['status'] === 'INACTIVE' && $user->roles()->where('roles.code', 'SUPER_ADMIN')->wherePivot('status', 'ACTIVE')->exists(), 422, 'A protected Super Administrator account cannot be disabled.');
        $service->status($user, $data['status'], $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => $data['status'] === 'ACTIVE' ? 'User enabled.' : 'User disabled.']);
    }

    public function resetPassword(Request $request, User $user, UserAdministrationService $service): RedirectResponse
    {
        $this->assertUniversityManaged($user);
        abort_unless($request->user()->hasPermission('user.reset_password'), 403);
        $status = Password::sendResetLink(['email' => $user->email]);
        if ($status !== Password::RESET_LINK_SENT) {
            return back()->with('toast', ['type' => 'error', 'message' => __($status)]);
        }
        $service->auditPasswordReset($user, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Password reset link sent.']);
    }
    private function assertUniversityManaged(User $user): void
    {
        abort_unless($user->account_type !== 'APPLICANT' && $user->isUniversityManaged(), 404);
    }
}
