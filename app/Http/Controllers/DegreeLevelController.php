<?php

namespace App\Http\Controllers;

use App\Models\DegreeLevel;
use App\Models\University;
use App\Services\DegreeLevelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DegreeLevelController extends Controller
{
    public function __construct(private DegreeLevelService $service) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasPermission('degree_level.view'), 403);
        $university = University::firstOrFail();

        return Inertia::render('degree-levels/index', ['levels' => DegreeLevel::where('university_id', $university->id)->orderBy('display_order')->orderBy('name')->get(), 'can' => ['create' => $request->user()->hasPermission('degree_level.create'), 'update' => $request->user()->hasPermission('degree_level.update'), 'disable' => $request->user()->hasPermission('degree_level.disable')]]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('degree_level.create'), 403);
        $university = University::firstOrFail();
        $this->service->create($request->validate($this->rules($university->id)), $university->id, $request->user()->id, $request->ip());

        return back()->with('success', 'Degree level created.');
    }

    public function update(Request $request, DegreeLevel $degreeLevel): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('degree_level.update'), 403);
        $this->owned($degreeLevel);
        $this->service->update($degreeLevel, $request->validate($this->rules($degreeLevel->university_id, $degreeLevel)), $request->user()->id, $request->ip());

        return back()->with('success', 'Degree level updated.');
    }

    public function status(Request $request, DegreeLevel $degreeLevel): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('degree_level.disable'), 403);
        $this->owned($degreeLevel);
        $data = $request->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]]);
        $this->service->status($degreeLevel, $data['status'], $request->user()->id, $request->ip());

        return back()->with('success', 'Degree level status updated.');
    }

    private function rules(int $universityId, ?DegreeLevel $level = null): array
    {
        return ['name' => ['required', 'string', 'max:100'], 'code' => ['required', 'string', 'max:40', Rule::unique('degree_levels')->where('university_id', $universityId)->ignore($level)], 'description' => ['nullable', 'string', 'max:1000'], 'display_order' => ['required', 'integer', 'min:0', 'max:65535'], 'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]];
    }

    private function owned(DegreeLevel $level): void
    {
        abort_unless($level->university_id === University::firstOrFail()->id, 404);
    }
}
