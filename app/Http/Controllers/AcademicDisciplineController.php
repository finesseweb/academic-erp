<?php

namespace App\Http\Controllers;

use App\Models\AcademicDiscipline;
use App\Models\University;
use App\Services\AcademicMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AcademicDisciplineController extends Controller
{
    public function __construct(private AcademicMasterService $service) {}

    public function index(Request $r): Response
    {
        $this->authorize($r, 'view');
        $u = University::firstOrFail();
        $base = AcademicDiscipline::where('university_id', $u->id);

        return Inertia::render('academic-masters/disciplines', ['disciplines' => (clone $base)->with('parent:id,name')->withCount('specializations')->orderBy('display_order')->orderBy('name')->get(), 'parents' => (clone $base)->where('kind', 'DISCIPLINE')->where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name']), 'can' => $this->can($r)]);
    }

    public function store(Request $r): RedirectResponse
    {
        $this->authorize($r, 'create');
        $u = University::firstOrFail();
        $data = $this->validated($r, $u->id);
        $this->service->create(AcademicDiscipline::class, $data, $u->id, $r->user()->id, $r->ip(), 'AcademicDiscipline', 'DISCIPLINE');

        return back()->with('success', 'Discipline or specialization created.');
    }

    public function update(Request $r, AcademicDiscipline $discipline): RedirectResponse
    {
        $this->authorize($r, 'update');
        $this->owned($discipline);
        $this->service->update($discipline, $this->validated($r, $discipline->university_id, $discipline), $r->user()->id, $r->ip(), 'AcademicDiscipline', 'DISCIPLINE');

        return back()->with('success', 'Discipline or specialization updated.');
    }

    public function status(Request $r, AcademicDiscipline $discipline): RedirectResponse
    {
        $this->authorize($r, 'disable');
        $this->owned($discipline);
        $s = $r->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]])['status'];
        $this->service->status($discipline, $s, $r->user()->id, $r->ip(), 'AcademicDiscipline', 'DISCIPLINE');

        return back()->with('success', 'Academic discipline status updated.');
    }

    private function validated(Request $r, int $uid, ?AcademicDiscipline $record = null): array
    {
        if ($r->input('parent_id') === 'none') {
            $r->merge(['parent_id' => null]);
        }$data = $r->validate(['kind' => ['required', Rule::in(['DISCIPLINE', 'SPECIALIZATION'])], 'parent_id' => ['nullable', Rule::exists('academic_disciplines', 'id')->where(fn ($q) => $q->where('university_id', $uid)->where('kind', 'DISCIPLINE')->where('status', 'ACTIVE'))], 'name' => ['required', 'string', 'max:120'], 'code' => ['required', 'string', 'max:40', Rule::unique('academic_disciplines')->where('university_id', $uid)->ignore($record)], 'description' => ['nullable', 'string', 'max:1000'], 'display_order' => ['required', 'integer', 'min:0', 'max:65535'], 'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]]);
        if ($data['kind'] === 'SPECIALIZATION' && ! $data['parent_id']) {
            throw ValidationException::withMessages(['parent_id' => 'Select an active parent discipline for a specialization.']);
        }
        if ($record?->kind === 'DISCIPLINE' && $data['kind'] === 'SPECIALIZATION' && $record->specializations()->exists()) {
            throw ValidationException::withMessages(['kind' => 'A discipline with specializations cannot be changed to a specialization.']);
        }
        if ($data['kind'] === 'DISCIPLINE') {
            $data['parent_id'] = null;
        }

        return $data;
    }

    private function authorize(Request $r, string $a): void
    {
        abort_unless($r->user()->hasPermission("discipline.$a"), 403);
    }

    private function owned(AcademicDiscipline $x): void
    {
        abort_unless($x->university_id === University::firstOrFail()->id, 404);
    }

    private function can(Request $r): array
    {
        return ['create' => $r->user()->hasPermission('discipline.create'), 'update' => $r->user()->hasPermission('discipline.update'), 'disable' => $r->user()->hasPermission('discipline.disable')];
    }
}
