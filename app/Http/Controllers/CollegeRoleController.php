<?php

namespace App\Http\Controllers;

use App\Models\College;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeRoleController extends Controller
{
    private function authorizeCollege(Request $request, College $college, string $permission): void
    {
        abort_unless($request->user()->hasCollegePermission($permission, $college->id), 403);
    }

    public function index(Request $request, College $college): Response
    {
        $this->authorizeCollege($request, $college, 'college_role.view');

        $search = trim((string) $request->query('search', ''));
        $status = strtoupper((string) $request->query('status', ''));
        $status = in_array($status, ['ACTIVE', 'INACTIVE'], true) ? $status : '';

        $baseQuery = Role::query()
            ->where('owner_scope_type', 'COLLEGE')
            ->where('owner_scope_reference', "college:{$college->id}")
            ->where('is_system_role', false);

        $summary = [
            'total' => (clone $baseQuery)->count(),
            'active' => (clone $baseQuery)->where('status', 'ACTIVE')->count(),
            'inactive' => (clone $baseQuery)->where('status', 'INACTIVE')->count(),
        ];

        $roles = (clone $baseQuery)
            ->withCount(['permissions', 'users'])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('college-roles/index', [
            'college' => $college->only(['id', 'name', 'code']),
            'roles' => $roles,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
            'summary' => $summary,
            'can' => [
                'create' => $request->user()->hasCollegePermission('college_role.create', $college->id),
                'update' => $request->user()->hasCollegePermission('college_role.update', $college->id),
                'disable' => $request->user()->hasCollegePermission('college_role.disable', $college->id),
                'managePermissions' => $request->user()->hasCollegePermission('college_permission.assign', $college->id)
                    || $request->user()->hasCollegePermission('college_permission.remove', $college->id),
            ],
        ]);
    }

    public function store(Request $request, College $college): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_role.create');
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'code' => ['required', 'string', 'max:80', 'regex:/^[A-Z0-9_]+$/', Rule::unique('roles')], 'description' => ['nullable', 'string', 'max:500']]);
        $creatorScopeType = $request->user()->primary_college_id === null ? 'UNIVERSITY' : 'COLLEGE';
        app(RoleService::class)->createForCollege($data, $college->id, $request->user()->id, $request->ip(), $creatorScopeType);

        return back()->with('toast', ['type' => 'success', 'message' => 'College role created.']);
    }

    public function edit(Request $request, College $college, Role $role): Response
    {
        $this->authorizeCollege($request, $college, 'college_role.update');
        $this->assertOwned($college, $role);

        return Inertia::render('college-roles/edit', ['college' => $college->only(['id', 'name', 'code']), 'role' => $role->only(['id', 'name', 'code', 'description', 'status'])]);
    }

    public function update(Request $request, College $college, Role $role, RoleService $service): RedirectResponse
    {
        $this->authorizeCollege($request, $college, 'college_role.update');
        $this->assertOwned($college, $role);
        $data = $request->validate(['name' => ['required', 'string', 'max:100'], 'code' => ['required', 'string', 'max:80', 'regex:/^[A-Z0-9_]+$/', Rule::unique('roles')->ignore($role)], 'description' => ['nullable', 'string', 'max:500']]);
        $service->update($role, $data, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'College role updated.']);
    }

    public function status(Request $request, College $college, Role $role, RoleService $service): RedirectResponse
    {
        $this->assertOwned($college, $role);
        $status = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]])['status'];
        $this->authorizeCollege($request, $college, $status === 'INACTIVE' ? 'college_role.disable' : 'college_role.update');
        $service->status($role, $status, $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'College role status updated.']);
    }

    private function assertOwned(College $college, Role $role): void
    {
        abort_unless($role->owner_scope_type === 'COLLEGE' && $role->owner_scope_reference === "college:{$college->id}" && ! $role->is_system_role, 404);
    }
}
