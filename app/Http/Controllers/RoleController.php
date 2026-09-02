<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\College;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('role.view'), 403);
        $request->merge([
            'status' => $request->input('status') === 'all' ? null : $request->input('status'),
            'type' => $request->input('type') === 'all' ? null : $request->input('type'),
            'college_id' => $request->input('college_id') === 'all' ? null : $request->input('college_id'),
        ]);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
            'type' => ['nullable', Rule::in(['SYSTEM', 'CUSTOM'])],
            'college_id' => ['nullable', 'integer', 'exists:colleges,id'],
            'sort' => ['nullable', Rule::in(['name', 'code', 'updated_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $q = Role::query()->withCount(['permissions', 'users']);
        $q->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($i) => $i
            ->where('name', 'like', "%{$s}%")
            ->orWhere('code', 'like', "%{$s}%")))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('is_system_role', $v === 'SYSTEM'))
            ->when($filters['college_id'] ?? null, fn ($q, $collegeId) => $q
                ->where('owner_scope_type', 'COLLEGE')
                ->where('owner_scope_reference', "college:{$collegeId}"));

        $roles = $q->orderBy($filters['sort'] ?? 'name', $filters['direction'] ?? 'asc')
            ->paginate(15)
            ->withQueryString();

        $ownerCollegeIds = $roles->getCollection()
            ->filter(fn (Role $role) => $role->owner_scope_type === 'COLLEGE')
            ->map(function (Role $role): ?int {
                if (! preg_match('/^college:(\d+)$/', (string) $role->owner_scope_reference, $matches)) {
                    return null;
                }

                return (int) $matches[1];
            })
            ->filter()
            ->unique()
            ->values();

        $ownerColleges = College::query()
            ->whereIn('id', $ownerCollegeIds)
            ->get(['id', 'name', 'code'])
            ->keyBy('id');

        $roles->getCollection()->transform(function (Role $role) use ($ownerColleges): Role {
            $ownerCollege = null;
            $ownerCollegeId = null;

            if ($role->owner_scope_type === 'COLLEGE'
                && preg_match('/^college:(\d+)$/', (string) $role->owner_scope_reference, $matches)) {
                $ownerCollegeId = (int) $matches[1];
                $college = $ownerColleges->get($ownerCollegeId);
                $ownerCollege = $college ? $college->only(['id', 'name', 'code']) : null;
            }

            $role->setAttribute('owner_college_id', $ownerCollegeId);
            $role->setAttribute('owner_college', $ownerCollege);

            return $role;
        });

        return Inertia::render('roles/index', [
            'roles' => $roles,
            'colleges' => College::query()->orderBy('name')->get(['id', 'name', 'code', 'status']),
            'filters' => $filters,
            'summary' => [
                'total' => Role::count(),
                'system' => Role::where('is_system_role', true)->count(),
                'custom' => Role::where('is_system_role', false)->count(),
            ],
            'can' => [
                'create' => $request->user()->hasPermission('role.create'),
                'update' => $request->user()->hasPermission('role.update'),
                'changeStatus' => $request->user()->hasPermission('role.disable'),
                'viewPermissions' => $request->user()->hasPermission('permission.view'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('role.create'), 403);

        return Inertia::render('roles/create');
    }

    public function store(StoreRoleRequest $request, RoleService $service): RedirectResponse
    {
        $role = $service->create($request->validated(), $request->user()->id, $request->ip());

        return to_route('roles.edit', $role)->with('toast', ['type' => 'success', 'message' => 'Role created.']);
    }

    public function edit(Request $request, Role $role): Response
    {
        abort_unless($request->user()->hasPermission('role.view') && $request->user()->hasPermission('role.update'), 403);

        return Inertia::render('roles/edit', ['role' => $role->loadCount(['permissions', 'users'])]);
    }

    public function update(UpdateRoleRequest $request, Role $role, RoleService $service): RedirectResponse
    {
        abort_if($role->is_system_role, 422, 'System role identity is protected.');
        $service->update($role, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Role updated.']);
    }

    public function status(Request $request, Role $role, RoleService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('role.disable'), 403);
        abort_if($role->is_system_role, 422, 'System roles cannot be disabled.');
        $data = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]]);
        $service->status($role, $data['status'], $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => $data['status'] === 'ACTIVE' ? 'Role activated.' : 'Role deactivated.']);
    }
}
