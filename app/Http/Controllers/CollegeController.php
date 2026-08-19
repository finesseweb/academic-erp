<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCollegeRequest;
use App\Http\Requests\UpdateCollegeRequest;
use App\Models\College;
use App\Models\University;
use App\Services\CollegeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CollegeController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('college.view'), 403);
        $request->merge([
            'status' => $request->input('status') === 'all' ? null : $request->input('status'),
            'affiliation_type' => $request->input('affiliation_type') === 'all' ? null : $request->input('affiliation_type'),
        ]);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
            'affiliation_type' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', Rule::in(['name', 'code', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $university = University::query()->firstOrFail();
        $query = $university->colleges()->with('principal:id,name');
        $query->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($inner) => $inner->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%")->orWhere('city', 'like', "%{$search}%")));
        $query->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status));
        $query->when($filters['affiliation_type'] ?? null, fn ($q, $type) => $q->where('affiliation_type', $type));
        $sort = $filters['sort'] ?? 'name';
        $direction = $filters['direction'] ?? 'asc';

        return Inertia::render('colleges/index', [
            'colleges' => $query->orderBy($sort, $direction)->paginate(15)->withQueryString(),
            'filters' => $filters,
            'summary' => [
                'total' => $university->colleges()->count(),
                'active' => $university->colleges()->where('status', 'ACTIVE')->count(),
                'inactive' => $university->colleges()->where('status', 'INACTIVE')->count(),
            ],
            'can' => [
                'create' => $request->user()->hasPermission('college.create'),
                'update' => $request->user()->hasPermission('college.update'),
                'changeStatus' => $request->user()->hasPermission('college.disable'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('college.create'), 403);

        return Inertia::render('colleges/create');
    }

    public function store(StoreCollegeRequest $request, CollegeService $service): RedirectResponse
    {
        $college = $service->create(University::query()->firstOrFail(), $request->validated(), $request->user()->id, $request->ip());

        return to_route('colleges.edit', $college)->with('toast', ['type' => 'success', 'message' => 'Affiliated College created.']);
    }

    public function edit(Request $request, College $college): Response
    {
        abort_unless($request->user()->hasPermission('college.view') && $request->user()->hasPermission('college.update'), 403);
        $this->ensureUniversityCollege($college);

        return Inertia::render('colleges/edit', ['college' => $college]);
    }

    public function update(UpdateCollegeRequest $request, College $college, CollegeService $service): RedirectResponse
    {
        $this->ensureUniversityCollege($college);
        $service->update($college, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Affiliated College updated.']);
    }

    public function status(Request $request, College $college, CollegeService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('college.disable'), 403);
        $this->ensureUniversityCollege($college);
        $data = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]]);
        $service->changeStatus($college, $data['status'], $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => $data['status'] === 'ACTIVE' ? 'Affiliated College activated.' : 'Affiliated College deactivated.']);
    }

    private function ensureUniversityCollege(College $college): void
    {
        abort_unless($college->university_id === University::query()->value('id'), 404);
    }
}
