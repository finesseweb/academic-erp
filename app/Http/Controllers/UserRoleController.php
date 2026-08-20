<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserRoleScopeRequest;
use App\Models\College;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use App\Services\UserRoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserRoleController extends Controller
{
    public function edit(Request $request, User $user): Response
    {
        abort_unless($request->user()->hasPermission('user.view') && $request->user()->hasPermission('role.view'), 403);

        $availableRoles = Role::query()->where('status', 'ACTIVE')->where(fn ($query) => $query->where('is_system_role', false)->orWhere('code', 'COLLEGE_ADMIN'))->with('permissions:id,code,description')->orderBy('name')->get(['id', 'name', 'code', 'description']);

        return Inertia::render('users/roles', ['managedUser' => $user->load(['roles' => fn ($q) => $q->with('permissions:id,code')]), 'availableRoles' => $availableRoles, 'colleges' => College::where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name', 'code']), 'can' => ['assign' => $request->user()->hasPermission('role.assign'), 'unassign' => $request->user()->hasPermission('role.unassign'), 'updateScope' => $request->user()->hasPermission('scope.update') && ! $request->user()->is($user)]]);
    }

    public function store(Request $request, User $user, UserRoleService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('role.assign'), 403);
        $data = $request->validate(['role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where(fn ($query) => $query->where('status', 'ACTIVE')->where(fn ($roles) => $roles->where('is_system_role', false)->orWhere('code', 'COLLEGE_ADMIN')))], 'scope_type' => ['required', Rule::in(['UNIVERSITY', 'COLLEGE'])], 'college_id' => ['nullable', 'required_if:scope_type,COLLEGE', 'integer', Rule::exists('colleges', 'id')->where(fn ($q) => $q->where('status', 'ACTIVE'))]]);
        $role = Role::findOrFail($data['role_id']);
        abort_if($role->status !== 'ACTIVE' || ($role->is_system_role && $role->code !== 'COLLEGE_ADMIN'), 422, 'This role cannot be assigned here.');
        if ($role->code === 'COLLEGE_ADMIN') {
            abort_unless($request->user()->hasPermission('college_admin.assign'), 403);
            abort_if($data['scope_type'] !== 'COLLEGE', 422, 'College Administrator requires one College scope.');
        }
        $scope = $data['scope_type'] === 'UNIVERSITY' ? ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university'] : ['scope_type' => 'COLLEGE', 'scope_reference' => 'college:'.College::findOrFail($data['college_id'])->id];
        $exists = UserRole::where('user_id', $user->id)->where('role_id', $role->id)
            ->when($role->code !== 'COLLEGE_ADMIN', fn ($query) => $query->where($scope))->exists();
        abort_if($exists, 422, 'This role and scope are already assigned.');
        $service->assign($user, $role, $scope, $request->user()->id, $request->ip());
        if ($role->code === 'COLLEGE_ADMIN') {
            $user->update(['account_type' => 'COLLEGE_STAFF', 'primary_college_id' => $data['college_id']]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Role assigned to user.']);
    }

    public function destroy(Request $request, User $user, UserRole $assignment, UserRoleService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('role.unassign'), 403);
        abort_unless($assignment->user_id === $user->id, 404);
        abort_if($assignment->role()->where('is_system_role', true)->where('code', '!=', 'COLLEGE_ADMIN')->exists(), 422, 'Protected system role assignments cannot be removed here.');
        abort_if($request->user()->is($user), 422, 'You cannot remove your own role assignment.');
        $service->remove($assignment, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Role assignment removed.']);
    }

    public function updateScope(UpdateUserRoleScopeRequest $request, User $user, UserRole $assignment, UserRoleService $service): RedirectResponse
    {
        abort_unless($assignment->user_id === $user->id, 404);
        abort_if($assignment->role()->where('is_system_role', true)->where('code', '!=', 'COLLEGE_ADMIN')->exists(), 422, 'Protected system role assignments cannot be changed here.');
        abort_if($request->user()->is($user), 422, 'You cannot change your own access scope.');

        $data = $request->validated();
        $assignment->load('role');
        if ($assignment->role->code === 'COLLEGE_ADMIN') {
            abort_if($data['scope_type'] !== 'COLLEGE', 422, 'College Administrator requires one College scope.');
        }
        $scope = $data['scope_type'] === 'UNIVERSITY'
            ? ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university']
            : ['scope_type' => 'COLLEGE', 'scope_reference' => 'college:'.College::findOrFail($data['college_id'])->id];

        $duplicate = UserRole::query()
            ->where('user_id', $user->id)
            ->where('role_id', $assignment->role_id)
            ->where($scope)
            ->whereKeyNot($assignment->id)
            ->exists();
        abort_if($duplicate, 422, 'This role and scope are already assigned.');

        $service->updateScope($assignment, [
            ...$scope,
            'status' => $data['status'],
            'effective_from' => $data['effective_from'] ?? null,
            'effective_until' => $data['effective_until'] ?? null,
        ], $request->user()->id, $request->ip());
        if ($assignment->role->code === 'COLLEGE_ADMIN') {
            $user->update(['account_type' => 'COLLEGE_STAFF', 'primary_college_id' => $data['college_id']]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Assignment scope updated.']);
    }
}
