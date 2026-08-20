<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Permission;
use App\Models\Role;
use App\Services\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollegeRolePermissionController extends Controller
{
    public function edit(Request $request, College $college, Role $role): Response
    {
        $this->assertOwned($college, $role);
        abort_unless($request->user()->hasCollegePermission('college_role.view', $college->id), 403);
        $ownCodes = $request->user()->permissionCodes('COLLEGE', "college:{$college->id}");
        $currentDelegableIds = $role->permissions()->where('is_college_delegable', true)->pluck('permissions.id');
        $permissions = Permission::query()->where('status', 'ACTIVE')->where('is_college_delegable', true)
            ->where(fn ($query) => $query->whereIn('code', $ownCodes)->orWhereIn('id', $currentDelegableIds))
            ->orderBy('module')->orderBy('resource')->orderBy('action')->get()
            ->each(fn (Permission $permission) => $permission->setAttribute('can_assign', in_array($permission->code, $ownCodes, true)));
        $allowedIds = $permissions->pluck('id');
        $role->setRelation('permissions', $role->permissions()->whereIn('permissions.id', $allowedIds)->get(['permissions.id']));
        $role->loadCount('users');

        return Inertia::render('roles/permissions', [
            'role' => $role,
            'permissions' => $permissions->groupBy('module')->map(fn ($items) => ['module' => $items->first()->module, 'permissions' => $items->values()])->values(),
            'can' => ['assign' => $request->user()->hasCollegePermission('college_permission.assign', $college->id), 'remove' => $request->user()->hasCollegePermission('college_permission.remove', $college->id)],
            'context' => ['eyebrow' => $college->code.' · Delegated College permissions', 'backUrl' => "/college/{$college->id}/roles", 'updateUrl' => "/college/{$college->id}/roles/{$role->id}/permissions"],
        ]);
    }

    public function update(Request $request, College $college, Role $role, RolePermissionService $service): RedirectResponse
    {
        $this->assertOwned($college, $role);
        $request->merge(['permission_ids' => $request->input('permission_ids', [])]);
        $data = $request->validate(['permission_ids' => ['present', 'array'], 'permission_ids.*' => ['integer', 'distinct', 'exists:permissions,id']]);
        $submitted = array_map('intval', $data['permission_ids']);
        $delegable = Permission::query()->where('status', 'ACTIVE')->where('is_college_delegable', true)->whereIn('id', $submitted)->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_if(count($delegable) !== count($submitted), 422, 'Only active College-delegable permissions may be selected.');
        $currentDelegable = $role->permissions()->where('is_college_delegable', true)->pluck('permissions.id')->map(fn ($id) => (int) $id)->all();
        $added = array_diff($submitted, $currentDelegable);
        $removed = array_diff($currentDelegable, $submitted);
        abort_if($added && ! $request->user()->hasCollegePermission('college_permission.assign', $college->id), 403);
        abort_if($removed && ! $request->user()->hasCollegePermission('college_permission.remove', $college->id), 403);
        $ownIds = Permission::query()->whereIn('code', $request->user()->permissionCodes('COLLEGE', "college:{$college->id}"))->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_if(array_diff($added, $ownIds), 403, 'You cannot delegate a permission that you do not hold in this College.');
        $protectedIds = $role->permissions()->where('is_college_delegable', false)->pluck('permissions.id')->map(fn ($id) => (int) $id)->all();
        $changes = $service->sync($role, [...$protectedIds, ...$submitted], $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => count($changes['added']) + count($changes['removed']) ? 'College role permissions updated.' : 'No permission changes were required.']);
    }

    private function assertOwned(College $college, Role $role): void
    {
        abort_unless(! $role->is_system_role && $role->owner_scope_type === 'COLLEGE' && $role->owner_scope_reference === "college:{$college->id}", 404);
    }
}
