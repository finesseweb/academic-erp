<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAuthorizedSignatoryRequest;
use App\Http\Requests\UpdateAuthorizedSignatoryRequest;
use App\Models\AuthorizedSignatory;
use App\Models\University;
use App\Services\AuthorizedSignatoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AuthorizedSignatoryController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('authorized_signatory.view'), 403);
        $request->merge([
            'status' => $request->input('status') === 'all' ? null : $request->input('status'),
            'authority_type' => $request->input('authority_type') === 'all' ? null : $request->input('authority_type'),
        ]);
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(['ACTIVE', 'INACTIVE'])],
            'authority_type' => ['nullable', Rule::in(['GENERAL', 'ACADEMIC_RECORDS', 'EXAMINATION', 'CERTIFICATES', 'FINANCE'])],
            'sort' => ['nullable', Rule::in(['full_name', 'designation', 'effective_from', 'created_at'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);
        $university = University::query()->firstOrFail();
        $query = $university->authorizedSignatories();
        $query->when($filters['search'] ?? null, fn ($q, $search) => $q->where(fn ($inner) => $inner->where('full_name', 'like', "%{$search}%")->orWhere('designation', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
        $query->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status));
        $query->when($filters['authority_type'] ?? null, fn ($q, $type) => $q->where('authority_type', $type));

        return Inertia::render('signatories/index', [
            'signatories' => $query->orderBy($filters['sort'] ?? 'full_name', $filters['direction'] ?? 'asc')->paginate(15)->withQueryString(),
            'filters' => $filters,
            'summary' => [
                'total' => $university->authorizedSignatories()->count(),
                'active' => $university->authorizedSignatories()->where('status', 'ACTIVE')->count(),
                'expiringSoon' => $university->authorizedSignatories()->where('status', 'ACTIVE')->whereBetween('effective_until', [today(), today()->addDays(30)])->count(),
            ],
            'can' => [
                'create' => $request->user()->hasPermission('authorized_signatory.create'),
                'update' => $request->user()->hasPermission('authorized_signatory.update'),
                'changeStatus' => $request->user()->hasPermission('authorized_signatory.disable'),
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('authorized_signatory.create'), 403);

        return Inertia::render('signatories/create');
    }

    public function store(StoreAuthorizedSignatoryRequest $request, AuthorizedSignatoryService $service): RedirectResponse
    {
        $signatory = $service->create(University::query()->firstOrFail(), $request->validated(), $request->user()->id, $request->ip());

        return to_route('signatories.edit', $signatory)->with('toast', ['type' => 'success', 'message' => 'Authorized signatory created.']);
    }

    public function edit(Request $request, AuthorizedSignatory $signatory): Response
    {
        abort_unless($request->user()->hasPermission('authorized_signatory.view') && $request->user()->hasPermission('authorized_signatory.update'), 403);
        $this->ensureUniversitySignatory($signatory);

        return Inertia::render('signatories/edit', ['signatory' => $signatory]);
    }

    public function update(UpdateAuthorizedSignatoryRequest $request, AuthorizedSignatory $signatory, AuthorizedSignatoryService $service): RedirectResponse
    {
        $this->ensureUniversitySignatory($signatory);
        $service->update($signatory, $request->validated(), $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => 'Authorized signatory updated.']);
    }

    public function status(Request $request, AuthorizedSignatory $signatory, AuthorizedSignatoryService $service): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('authorized_signatory.disable'), 403);
        $this->ensureUniversitySignatory($signatory);
        $data = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]]);
        $service->changeStatus($signatory, $data['status'], $request->user()->id, $request->ip());

        return back()->with('toast', ['type' => 'success', 'message' => $data['status'] === 'ACTIVE' ? 'Authorized signatory activated.' : 'Authorized signatory deactivated.']);
    }

    private function ensureUniversitySignatory(AuthorizedSignatory $signatory): void
    {
        abort_unless($signatory->university_id === University::query()->value('id'), 404);
    }
}
