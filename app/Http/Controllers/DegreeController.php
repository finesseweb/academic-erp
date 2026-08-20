<?php

namespace App\Http\Controllers;

use App\Models\Degree;
use App\Models\DegreeLevel;
use App\Models\University;
use App\Services\AcademicMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DegreeController extends Controller
{
    public function __construct(private AcademicMasterService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('degree.view'), 403);
        $university = University::firstOrFail();

        return Inertia::render('academic-masters/degrees', ['degrees' => Degree::with('degreeLevel:id,name')->where('university_id', $university->id)->orderBy('display_order')->orderBy('name')->get(), 'levels' => DegreeLevel::where('university_id', $university->id)->where('status', 'ACTIVE')->orderBy('display_order')->get(['id', 'name']), 'can' => $this->can($request)]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('degree.create'), 403);
        $university = University::firstOrFail();
        $this->service->create(Degree::class, $request->validate($this->rules($university->id)), $university->id, $request->user()->id, $request->ip(), 'Degree', 'DEGREE');

        return back()->with('success', 'Degree created.');
    }

    public function update(Request $request, Degree $degree): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('degree.update'), 403);
        $this->owned($degree);
        $this->service->update($degree, $request->validate($this->rules($degree->university_id, $degree)), $request->user()->id, $request->ip(), 'Degree', 'DEGREE');

        return back()->with('success', 'Degree updated.');
    }

    public function status(Request $request, Degree $degree): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('degree.disable'), 403);
        $this->owned($degree);
        $status = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]])['status'];
        $this->service->status($degree, $status, $request->user()->id, $request->ip(), 'Degree', 'DEGREE');

        return back()->with('success', 'Degree status updated.');
    }

    private function rules(int $universityId, ?Degree $degree = null): array
    {
        return ['degree_level_id' => ['required', Rule::exists('degree_levels', 'id')->where(fn ($q) => $q->where('university_id', $universityId)->where('status', 'ACTIVE'))], 'name' => ['required', 'string', 'max:120'], 'code' => ['required', 'string', 'max:40', Rule::unique('degrees')->where('university_id', $universityId)->ignore($degree)], 'description' => ['nullable', 'string', 'max:1000'], 'typical_duration_years' => ['nullable', 'integer', 'min:1', 'max:15'], 'display_order' => ['required', 'integer', 'min:0', 'max:65535'], 'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]];
    }

    private function owned(Degree $degree): void
    {
        abort_unless($degree->university_id === University::firstOrFail()->id, 404);
    }

    private function can(Request $r): array
    {
        return ['create' => $r->user()->hasPermission('degree.create'), 'update' => $r->user()->hasPermission('degree.update'), 'disable' => $r->user()->hasPermission('degree.disable')];
    }
}
