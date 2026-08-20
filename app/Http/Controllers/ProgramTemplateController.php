<?php

namespace App\Http\Controllers;

use App\Models\AcademicDiscipline;
use App\Models\Degree;
use App\Models\ProgramTemplate;
use App\Models\University;
use App\Services\AcademicMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProgramTemplateController extends Controller
{
    public function __construct(private AcademicMasterService $service) {}

    public function index(Request $r): Response
    {
        $this->authorize($r, 'view');
        $u = University::firstOrFail();

        return Inertia::render('academic-masters/program-templates', ['templates' => ProgramTemplate::with(['degree:id,name', 'discipline:id,name'])->where('university_id', $u->id)->orderBy('display_order')->orderBy('name')->get(), 'degrees' => Degree::where('university_id', $u->id)->where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name']), 'disciplines' => AcademicDiscipline::where('university_id', $u->id)->where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name']), 'can' => $this->can($r)]);
    }

    public function store(Request $r): RedirectResponse
    {
        $this->authorize($r, 'create');
        $u = University::firstOrFail();
        $this->service->create(ProgramTemplate::class, $this->validated($r, $u->id), $u->id, $r->user()->id, $r->ip(), 'ProgramTemplate', 'PROGRAM_TEMPLATE');

        return back()->with('success', 'Program template created.');
    }

    public function update(Request $r, ProgramTemplate $programTemplate): RedirectResponse
    {
        $this->authorize($r, 'update');
        $this->owned($programTemplate);
        $this->service->update($programTemplate, $this->validated($r, $programTemplate->university_id, $programTemplate), $r->user()->id, $r->ip(), 'ProgramTemplate', 'PROGRAM_TEMPLATE');

        return back()->with('success', 'Program template updated.');
    }

    public function status(Request $r, ProgramTemplate $programTemplate): RedirectResponse
    {
        $this->authorize($r, 'disable');
        $this->owned($programTemplate);
        $s = $r->validate(['status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]])['status'];
        $this->service->status($programTemplate, $s, $r->user()->id, $r->ip(), 'ProgramTemplate', 'PROGRAM_TEMPLATE');

        return back()->with('success', 'Program template status updated.');
    }

    private function validated(Request $r, int $uid, ?ProgramTemplate $x = null): array
    {
        if ($r->input('discipline_id') === 'none') {
            $r->merge(['discipline_id' => null]);
        }

        return $r->validate(['degree_id' => ['required', Rule::exists('degrees', 'id')->where(fn ($q) => $q->where('university_id', $uid)->where('status', 'ACTIVE'))], 'discipline_id' => ['nullable', Rule::exists('academic_disciplines', 'id')->where(fn ($q) => $q->where('university_id', $uid)->where('status', 'ACTIVE'))], 'name' => ['required', 'string', 'max:150'], 'code' => ['required', 'string', 'max:40', Rule::unique('program_templates')->where('university_id', $uid)->ignore($x)], 'term_structure' => ['required', Rule::in(['SEMESTER', 'YEAR', 'TRIMESTER'])], 'duration_terms' => ['required', 'integer', 'min:1', 'max:30'], 'description' => ['nullable', 'string', 'max:1000'], 'display_order' => ['required', 'integer', 'min:0', 'max:65535'], 'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]]);
    }

    private function authorize(Request $r, string $a): void
    {
        abort_unless($r->user()->hasPermission("program_template.$a"), 403);
    }

    private function owned(ProgramTemplate $x): void
    {
        abort_unless($x->university_id === University::firstOrFail()->id, 404);
    }

    private function can(Request $r): array
    {
        return ['create' => $r->user()->hasPermission('program_template.create'), 'update' => $r->user()->hasPermission('program_template.update'), 'disable' => $r->user()->hasPermission('program_template.disable')];
    }
}
