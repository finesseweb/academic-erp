<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('permission.view'), 403);
        $request->merge([
            'module' => $request->input('module') === 'all' ? null : $request->input('module'),
            'status' => $request->input('status') === 'all' ? null : $request->input('status'),
            'sensitive' => $request->input('sensitive') === 'all' ? null : $request->input('sensitive'),
        ]);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'], 'module' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])], 'sensitive' => ['nullable', Rule::in(['yes', 'no'])],
            'sort' => ['nullable', Rule::in(['module', 'resource', 'action', 'code'])], 'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $query = Permission::query();
        $query->when($filters['search'] ?? null, fn ($q, $s) => $q->where(fn ($inner) => $inner->where('code', 'like', "%{$s}%")->orWhere('description', 'like', "%{$s}%")->orWhere('resource', 'like', "%{$s}%")))
            ->when($filters['module'] ?? null, fn ($q, $v) => $q->where('module', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(isset($filters['sensitive']), fn ($q) => $q->where('is_sensitive', $filters['sensitive'] === 'yes'));

        return Inertia::render('permissions/index', [
            'permissions' => $query->orderBy($filters['sort'] ?? 'module', $filters['direction'] ?? 'asc')->orderBy('code')->paginate(20)->withQueryString(),
            'modules' => Permission::query()->distinct()->orderBy('module')->pluck('module'), 'filters' => $filters,
            'summary' => ['total' => Permission::count(), 'active' => Permission::where('status', 'ACTIVE')->count(), 'sensitive' => Permission::where('is_sensitive', true)->count()],
        ]);
    }
}
