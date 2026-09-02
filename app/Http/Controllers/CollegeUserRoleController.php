<?php

namespace App\Http\Controllers;

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

class CollegeUserRoleController extends Controller
{
    public function edit(Request $request, College $college, User $user): Response
    {
        $this->assertUserOwned($college, $user);
        abort_unless($request->user()->hasCollegePermission('college_user.view', $college->id) && $request->user()->hasCollegePermission('college_role.view', $college->id), 403);
        $roles = Role::query()->where('owner_scope_type', 'COLLEGE')->where('owner_scope_reference', "college:{$college->id}")->where('status', 'ACTIVE')->with('permissions:id,code,description')->orderBy('name')->get();
        $assignments = UserRole::query()->where('user_id', $user->id)->where('scope_type', 'COLLEGE')->where('scope_reference', "college:{$college->id}")
            ->whereHas('role', fn ($query) => $query->where('owner_scope_type', 'COLLEGE')->where('owner_scope_reference', "college:{$college->id}"))->with('role.permissions:id,code')->get();

        return Inertia::render('college-users/roles', ['college' => $college->only(['id', 'name', 'code']), 'managedUser' => $user->only(['id', 'name', 'email', 'status']), 'availableRoles' => $roles, 'assignments' => $assignments, 'can' => ['assign' => $request->user()->hasCollegePermission('college_role.assign', $college->id) && ! $request->user()->is($user), 'unassign' => $request->user()->hasCollegePermission('college_role.unassign', $college->id) && ! $request->user()->is($user), 'updateScope' => $request->user()->hasCollegePermission('college_scope.update', $college->id) && ! $request->user()->is($user)]]);
    }

    public function store(Request $request, College $college, User $user, UserRoleService $service): RedirectResponse
    {
        $this->assertUserOwned($college, $user);
        abort_unless($request->user()->hasCollegePermission('college_role.assign', $college->id), 403);
        abort_if($request->user()->is($user), 422, 'You cannot assign roles to your own account.');
        $data = $request->validate(['role_id' => ['required', 'integer', Rule::exists('roles', 'id')->where(fn ($query) => $query->where('status', 'ACTIVE')->where('is_system_role', false)->where('owner_scope_type', 'COLLEGE')->where('owner_scope_reference', "college:{$college->id}"))]]);
        $role = Role::findOrFail($data['role_id']);
        $scope = ['scope_type' => 'COLLEGE', 'scope_reference' => "college:{$college->id}"];
        abort_if(UserRole::query()->where('user_id', $user->id)->where('role_id', $role->id)->where($scope)->exists(), 422, 'This College role is already assigned.');
        $service->assign($user, $role, $scope, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'College role assigned.']);
    }

    public function destroy(Request $request, College $college, User $user, UserRole $assignment, UserRoleService $service): RedirectResponse
    {
        $this->assertAssignmentOwned($college, $user, $assignment);
        abort_unless($request->user()->hasCollegePermission('college_role.unassign', $college->id), 403);
        abort_if($request->user()->is($user), 422, 'You cannot remove your own role assignment.');
        $service->remove($assignment, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'College role assignment removed.']);
    }

    public function updateScope(Request $request, College $college, User $user, UserRole $assignment, UserRoleService $service): RedirectResponse
    {
        $this->assertAssignmentOwned($college, $user, $assignment);
        abort_unless($request->user()->hasCollegePermission('college_scope.update', $college->id), 403);
        abort_if($request->user()->is($user), 422, 'You cannot change your own role lifecycle.');
        $data = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])], 'effective_from' => ['nullable', 'date'], 'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from']]);
        $service->updateScope($assignment, ['scope_type' => 'COLLEGE', 'scope_reference' => "college:{$college->id}", ...$data], $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'College assignment lifecycle updated.']);
    }

    private function assertUserOwned(College $college, User $user): void
    {
        abort_unless($user->primary_college_id === $college->id && $user->account_type === 'COLLEGE_STAFF', 404);
    }

    private function assertAssignmentOwned(College $college, User $user, UserRole $assignment): void
    {
        $this->assertUserOwned($college, $user);
        abort_unless($assignment->user_id === $user->id && $assignment->scope_type === 'COLLEGE' && $assignment->scope_reference === "college:{$college->id}", 404);
        abort_unless($assignment->role()->where('is_system_role', false)->where('owner_scope_type', 'COLLEGE')->where('owner_scope_reference', "college:{$college->id}")->exists(), 404);
    }
}
