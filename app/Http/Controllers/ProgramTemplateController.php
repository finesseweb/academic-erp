<?php

namespace App\Http\Controllers;

use App\Models\AcademicDiscipline;
use App\Models\Degree;
use App\Models\ProgramTemplate;
use App\Models\ProgramTemplateDiscipline;
use App\Models\University;
use App\Services\AcademicMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ProgramTemplateController extends Controller
{
    public function __construct(private AcademicMasterService $service) {}

    public function index(Request $r): Response
    {
        $this->authorize($r, 'view');
        $u = University::firstOrFail();

        $templates = ProgramTemplate::query()
            ->with([
                'degree:id,name',
                'disciplineMappings' => fn ($q) => $q->with([
                    'discipline:id,name',
                    'specializations:id,name,parent_id',
                ])->orderBy('id'),
            ])
            ->where('university_id', $u->id)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return Inertia::render('academic-masters/program-templates', [
            'templates' => $templates,
            'degrees' => Degree::where('university_id', $u->id)
                ->where('status', 'ACTIVE')
                ->orderBy('name')
                ->get(['id', 'name']),
            'disciplines' => AcademicDiscipline::where('university_id', $u->id)
                ->where('kind', 'DISCIPLINE')
                ->where('status', 'ACTIVE')
                ->orderBy('name')
                ->get(['id', 'name']),
            'specializations' => AcademicDiscipline::where('university_id', $u->id)
                ->where('kind', 'SPECIALIZATION')
                ->where('status', 'ACTIVE')
                ->orderBy('name')
                ->get(['id', 'parent_id', 'name']),
            'can' => $this->can($r),
        ]);
    }

    public function store(Request $r): RedirectResponse
    {
        $this->authorize($r, 'create');
        $u = University::firstOrFail();
        $data = $this->validated($r, $u->id);

        DB::transaction(function () use ($r, $u, $data) {
            $templateData = $this->templateData($data);

            $this->service->create(
                ProgramTemplate::class,
                $templateData,
                $u->id,
                $r->user()->id,
                $r->ip(),
                'ProgramTemplate',
                'PROGRAM_TEMPLATE'
            );

            // AcademicMasterService may return void, so resolve the newly-created
            // template by its university-scoped unique code.
            $template = ProgramTemplate::where('university_id', $u->id)
                ->where('code', $templateData['code'])
                ->firstOrFail();

            $this->syncAcademicStructure($template, $data['disciplines']);
        });

        return back()->with('success', 'Program template created.');
    }

    public function update(Request $r, ProgramTemplate $programTemplate): RedirectResponse
    {
        $this->authorize($r, 'update');
        $this->owned($programTemplate);

        $data = $this->validated($r, $programTemplate->university_id, $programTemplate);

        DB::transaction(function () use ($r, $programTemplate, $data) {
            $this->service->update(
                $programTemplate,
                $this->templateData($data),
                $r->user()->id,
                $r->ip(),
                'ProgramTemplate',
                'PROGRAM_TEMPLATE'
            );

            $this->syncAcademicStructure($programTemplate, $data['disciplines']);
        });

        return back()->with('success', 'Program template updated.');
    }

    public function status(Request $r, ProgramTemplate $programTemplate): RedirectResponse
    {
        $this->authorize($r, 'disable');
        $this->owned($programTemplate);

        $status = $r->validate([
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ])['status'];

        $this->service->status(
            $programTemplate,
            $status,
            $r->user()->id,
            $r->ip(),
            'ProgramTemplate',
            'PROGRAM_TEMPLATE'
        );

        return back()->with('success', 'Program template status updated.');
    }

    private function validated(Request $r, int $uid, ?ProgramTemplate $template = null): array
    {
        $data = $r->validate([
            'degree_id' => [
                'required',
                Rule::exists('degrees', 'id')->where(
                    fn ($q) => $q->where('university_id', $uid)->where('status', 'ACTIVE')
                ),
            ],
            'disciplines' => ['required', 'array', 'min:1'],
            'disciplines.*.discipline_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('academic_disciplines', 'id')->where(
                    fn ($q) => $q->where('university_id', $uid)
                        ->where('kind', 'DISCIPLINE')
                        ->where('status', 'ACTIVE')
                ),
            ],
            'disciplines.*.specialization_ids' => ['nullable', 'array'],
            'disciplines.*.specialization_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('academic_disciplines', 'id')->where(
                    fn ($q) => $q->where('university_id', $uid)
                        ->where('kind', 'SPECIALIZATION')
                        ->where('status', 'ACTIVE')
                ),
            ],
            'name' => ['required', 'string', 'max:150'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('program_templates')
                    ->where('university_id', $uid)
                    ->ignore($template),
            ],
            'term_structure' => ['required', Rule::in(['SEMESTER', 'YEAR', 'TRIMESTER'])],
            'duration_terms' => ['required', 'integer', 'min:1', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
            'display_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ]);

        foreach ($data['disciplines'] as $index => $row) {
            $disciplineId = (int) $row['discipline_id'];
            $specializationIds = array_map('intval', $row['specialization_ids'] ?? []);

            if (! $specializationIds) {
                continue;
            }

            $validCount = AcademicDiscipline::where('university_id', $uid)
                ->where('kind', 'SPECIALIZATION')
                ->where('status', 'ACTIVE')
                ->where('parent_id', $disciplineId)
                ->whereIn('id', $specializationIds)
                ->count();

            if ($validCount !== count(array_unique($specializationIds))) {
                throw ValidationException::withMessages([
                    "disciplines.$index.specialization_ids" =>
                        'Every specialization must belong to its selected discipline.',
                ]);
            }
        }

        return $data;
    }

    private function templateData(array $data): array
    {
        return collect($data)
            ->except('disciplines')
            ->all();
    }

    private function syncAcademicStructure(ProgramTemplate $template, array $rows): void
    {
        $selectedDisciplineIds = collect($rows)
            ->pluck('discipline_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        // Remove discipline mappings that the user unchecked.
        $template->disciplineMappings()
            ->whereNotIn('discipline_id', $selectedDisciplineIds)
            ->delete();

        foreach ($rows as $row) {
            $mapping = ProgramTemplateDiscipline::firstOrCreate([
                'program_template_id' => $template->id,
                'discipline_id' => (int) $row['discipline_id'],
            ]);

            $mapping->specializations()->sync(
                array_map('intval', $row['specialization_ids'] ?? [])
            );
        }
    }

    private function authorize(Request $r, string $action): void
    {
        abort_unless($r->user()->hasPermission("program_template.$action"), 403);
    }

    private function owned(ProgramTemplate $template): void
    {
        abort_unless(
            $template->university_id === University::firstOrFail()->id,
            404
        );
    }

    private function can(Request $r): array
    {
        return [
            'create' => $r->user()->hasPermission('program_template.create'),
            'update' => $r->user()->hasPermission('program_template.update'),
            'disable' => $r->user()->hasPermission('program_template.disable'),
        ];
    }
}
