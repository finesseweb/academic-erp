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

        return Inertia::render('users/roles', ['managedUser' => $user->load(['roles' => fn ($q) => $q->with('permissions:id,code')]), 'availableRoles' => Role::where('status', 'ACTIVE')->where('is_system_role', false)->with('permissions:id,code,description')->orderBy('name')->get(['id', 'name', 'code', 'description']), 'colleges' => College::where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name', 'code']), 'can' => ['assign' => $request->user()->hasPermission('role.assign'), 'unassign' => $request->user()->hasPermission('role.unassign'), 'updateScope' => $request->user()->hasPermission('scope.update') && ! $request->user()->is($user)]]);
    }

    public function store(Request $request, User $user, UserRoleService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('role.assign'), 403);
        $data = $request->validate(['role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where(fn ($q) => $q->where('status', 'ACTIVE')->where('is_system_role', false))], 'scope_type' => ['required', Rule::in(['UNIVERSITY', 'COLLEGE'])], 'college_id' => ['nullable', 'required_if:scope_type,COLLEGE', 'integer', 'exists:colleges,id']]);
        $role = Role::findOrFail($data['role_id']);
        $scope = $data['scope_type'] === 'UNIVERSITY' ? ['scope_type' => 'UNIVERSITY', 'scope_reference' => 'university'] : ['scope_type' => 'COLLEGE', 'scope_reference' => 'college:'.College::findOrFail($data['college_id'])->id];
        $exists = UserRole::where('user_id', $user->id)->where('role_id', $role->id)->where($scope)->exists();
        abort_if($exists, 422, 'This role and scope are already assigned.');
        $service->assign($user, $role, $scope, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Role assigned to user.']);
    }

    public function destroy(Request $request, User $user, UserRole $assignment, UserRoleService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('role.unassign'), 403);
        abort_unless($assignment->user_id === $user->id, 404);
        abort_if($assignment->role()->where('is_system_role', true)->exists(), 422, 'Protected system role assignments cannot be removed here.');
        abort_if($request->user()->is($user), 422, 'You cannot remove your own role assignment.');
        $service->remove($assignment, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Role assignment removed.']);
    }

    public function updateScope(UpdateUserRoleScopeRequest $request, User $user, UserRole $assignment, UserRoleService $service): RedirectResponse
    {
        abort_unless($assignment->user_id === $user->id, 404);
        abort_if($assignment->role()->where('is_system_role', true)->exists(), 422, 'Protected system role assignments cannot be changed here.');
        abort_if($request->user()->is($user), 422, 'You cannot change your own access scope.');

        $data = $request->validated();
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

        return back()->with('toast', ['type' => 'success', 'message' => 'Assignment scope updated.']);
    }
}
