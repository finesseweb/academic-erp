<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Services\RolePermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RolePermissionController extends Controller
{
    public function edit(Request $request, Role $role): Response
    {
        abort_unless($request->user()->hasPermission('role.view') && $request->user()->hasPermission('permission.view'), 403);
        $permissions = Permission::query()->where('status', 'ACTIVE')->orderBy('module')->orderBy('resource')->orderBy('action')->get();

        return Inertia::render('roles/permissions', [
            'role' => $role->loadCount(['users'])->load('permissions:id'),
            'permissions' => $permissions->groupBy('module')->map(fn ($items) => $items->values())->values()->map(fn ($items) => ['module' => $items->first()->module, 'permissions' => $items])->all(),
            'can' => [
                'assign' => $request->user()->hasPermission('permission.assign_to_role'),
                'remove' => $request->user()->hasPermission('permission.remove_from_role'),
            ],
        ]);
    }

    public function update(Request $request, Role $role, RolePermissionService $service): RedirectResponse
    {
        abort_if($role->is_system_role, 422, 'System role permissions are managed by application migrations.');
        $request->merge(['permission_ids' => $request->input('permission_ids', [])]);
        $data = $request->validate(['permission_ids' => ['present', 'array'], 'permission_ids.*' => ['integer', 'distinct', 'exists:permissions,id']]);
        $activeIds = Permission::query()->where('status', 'ACTIVE')->whereIn('id', $data['permission_ids'])->pluck('id')->map(fn ($id) => (int) $id)->all();
        abort_if(count($activeIds) !== count($data['permission_ids']), 422, 'Only active implemented permissions can be assigned.');
        $currentIds = $role->permissions()->pluck('permissions.id')->map(fn ($id) => (int) $id)->all();
        abort_if(array_diff($activeIds, $currentIds) && ! $request->user()->hasPermission('permission.assign_to_role'), 403);
        abort_if(array_diff($currentIds, $activeIds) && ! $request->user()->hasPermission('permission.remove_from_role'), 403);
        $changes = $service->sync($role, $activeIds, $request->user()->id, $request->ip());
        $count = count($changes['added']) + count($changes['removed']);

        return back()->with('toast', ['type' => 'success', 'message' => $count ? "Role permissions updated ({$count} changes)." : 'No permission changes were required.']);
    }
}
