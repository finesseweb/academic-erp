<?php

namespace App\Http\Controllers;

use App\Http\Requests\CloneCurriculumSlotRequest;
use App\Http\Requests\CloneCurriculumTermRequest;
use App\Http\Requests\StoreCurriculumCourseMappingRequest;
use App\Http\Requests\UpdateCurriculumCourseMappingOrderRequest;
use App\Http\Requests\UpdateCurriculumCourseMappingRequest;
use App\Http\Requests\StoreCurriculumSlotRequest;
use App\Http\Requests\StoreCurriculumTermRequest;
use App\Http\Requests\UpdateCurriculumSlotRequest;
use App\Http\Requests\UpdateCurriculumTermRequest;
use App\Models\Curriculum;
use App\Models\CurriculumCourseMapping;
use App\Models\CurriculumSlot;
use App\Models\CurriculumTerm;
use App\Services\CurriculumCloneService;
use App\Services\CurriculumCourseMappingService;
use App\Services\CurriculumCreditSummaryService;
use App\Services\CurriculumStructureDeleteService;
use App\Services\CurriculumStructureValidationService;
use App\Services\CurriculumSlotService;
use App\Services\CurriculumTermService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CurriculumStructureController extends Controller
{
    public function __construct(
        private readonly CurriculumTermService $termService,
        private readonly CurriculumSlotService $slotService,
        private readonly CurriculumCloneService $cloneService,
        private readonly CurriculumStructureDeleteService $deleteService,
        private readonly CurriculumCourseMappingService $courseMappingService,
        private readonly CurriculumCreditSummaryService $creditSummaryService,
        private readonly CurriculumStructureValidationService $structureValidationService,
    ) {}

    public function terms(Request $request, Curriculum $curriculum): Response
    {
        abort_unless($request->user()->hasPermission('curriculum.view'), 403);

        $curriculum->load([
            'programTemplate:id,name,code',
            'academicSession:id,name,code',
            'terms',
        ]);

        return Inertia::render('admin/curricula/structure/terms', [
            'curriculum' => [
                'id' => $curriculum->id,
                'code' => $curriculum->code,
                'name' => $curriculum->name,
                'version' => $curriculum->version,
                'lifecycle_status' => $curriculum->lifecycle_status,
                'program_template' => $curriculum->programTemplate,
                'academic_session' => $curriculum->academicSession,
                'terms' => $curriculum->terms->map(fn (CurriculumTerm $term) => [
                    'id' => $term->id,
                    'sequence_no' => $term->sequence_no,
                    'name' => $term->name,
                    'status' => $term->status,
                ])->values(),
            ],
            'creditSummary' =>
                $this->creditSummaryService->summarize($curriculum),
            'permissions' => [
                'update' => $request->user()->hasPermission('curriculum.update'),
            ],
            'structureEditable' => $curriculum->lifecycle_status === 'DRAFT',
            'cloneTargets' => Curriculum::query()
                ->where('university_id', $curriculum->university_id)
                ->where('program_template_id', $curriculum->program_template_id)
                ->where('lifecycle_status', 'DRAFT')
                ->orderByDesc('id')
                ->get(['id', 'code', 'name', 'version'])
                ->values(),
        ]);
    }

    public function storeTerm(
        StoreCurriculumTermRequest $request,
        Curriculum $curriculum
    ): RedirectResponse {
        $this->termService->create(
            $curriculum,
            $request->validated(),
            $request->user()->id
        );

        return back()->with('success', 'Term / Semester added successfully.');
    }

    public function updateTerm(
        UpdateCurriculumTermRequest $request,
        Curriculum $curriculum,
        CurriculumTerm $term
    ): RedirectResponse {
        $this->termService->update(
            $curriculum,
            $term,
            $request->validated(),
            $request->user()->id
        );

        return back()->with('success', 'Term / Semester updated successfully.');
    }

    public function statusTerm(
        Request $request,
        Curriculum $curriculum,
        CurriculumTerm $term
    ): RedirectResponse {
        abort_unless(
            $request->user()->hasPermission('curriculum.update'),
            403
        );

        $validated = $request->validate([
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
        ]);

        $this->termService->setStatus(
            $curriculum,
            $term,
            $validated['status'],
            $request->user()->id
        );

        return back()->with('success', 'Term / Semester status updated successfully.');
    }

    public function slots(
        Request $request,
        Curriculum $curriculum,
        CurriculumTerm $term
    ): Response {
        abort_unless($request->user()->hasPermission('curriculum.view'), 403);

        abort_unless(
            (int) $term->curriculum_id === (int) $curriculum->id,
            404
        );

        $curriculum->load([
            'programTemplate:id,name,code',
            'academicSession:id,name,code',
        ]);

        $term->load('slots');

        $categoryIds = $term->slots
            ->pluck('course_category_id')
            ->filter()
            ->unique()
            ->values();

        $categories = DB::table('course_categories')
            ->where('university_id', $curriculum->university_id)
            ->where('status', 'ACTIVE')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $courseTypes = DB::table('course_types')
            ->where('university_id', $curriculum->university_id)
            ->where('status', 'ACTIVE')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $categoryNames = DB::table('course_categories')
            ->whereIn('id', $categoryIds)
            ->pluck('name', 'id');

        $courseTypeIds = $term->slots
            ->pluck('course_type_id')
            ->filter()
            ->unique()
            ->values();

        $courseTypeNames = DB::table('course_types')
            ->whereIn('id', $courseTypeIds)
            ->pluck('name', 'id');

        return Inertia::render('admin/curricula/structure/slots', [
            'curriculum' => [
                'id' => $curriculum->id,
                'code' => $curriculum->code,
                'name' => $curriculum->name,
                'version' => $curriculum->version,
                'lifecycle_status' => $curriculum->lifecycle_status,
                'program_template' => $curriculum->programTemplate,
                'academic_session' => $curriculum->academicSession,
            ],
            'term' => [
                'id' => $term->id,
                'sequence_no' => $term->sequence_no,
                'name' => $term->name,
                'status' => $term->status,
                'slots' => $term->slots->map(
                    fn (CurriculumSlot $slot) => [
                        'id' => $slot->id,
                        'course_category_id' => $slot->course_category_id,
                        'course_category_name' => $categoryNames[$slot->course_category_id] ?? 'Unknown',
                        'course_type_id' => $slot->course_type_id,
                        'course_type_name' => $slot->course_type_id
                            ? ($courseTypeNames[$slot->course_type_id] ?? 'Unknown')
                            : 'Not set',
                        'credits' => $slot->credits,
                        'name' => $slot->name,
                        'display_order' => $slot->display_order,
                        'selection_mode' => $slot->selection_mode,
                        'min_selection' => $slot->min_selection,
                        'max_selection' => $slot->max_selection,
                        'status' => $slot->status,
                    ]
                )->values(),
            ],
            'courseCategories' => $categories,
            'courseTypes' => $courseTypes,
            'allTerms' => CurriculumTerm::query()
                ->where('curriculum_id', $curriculum->id)
                ->orderBy('sequence_no')
                ->get(['id', 'sequence_no', 'name'])
                ->map(function (CurriculumTerm $targetTerm) {
                    $nextOrder = (int) CurriculumSlot::query()
                        ->where('curriculum_term_id', $targetTerm->id)
                        ->max('display_order') + 1;

                    return [
                        'id' => $targetTerm->id,
                        'sequence_no' => $targetTerm->sequence_no,
                        'name' => $targetTerm->name,
                        'next_slot_order' => max($nextOrder, 1),
                    ];
                })
                ->values(),

            'permissions' => [
                'update' => $request->user()->hasPermission('curriculum.update'),
            ],
            'structureEditable' => $curriculum->lifecycle_status === 'DRAFT',
            'cloneTargets' => Curriculum::query()
                ->where('university_id', $curriculum->university_id)
                ->where('program_template_id', $curriculum->program_template_id)
                ->where('lifecycle_status', 'DRAFT')
                ->orderByDesc('id')
                ->get(['id', 'code', 'name', 'version'])
                ->values(),
        ]);
    }

    public function storeSlot(
        StoreCurriculumSlotRequest $request,
        Curriculum $curriculum,
        CurriculumTerm $term
    ): RedirectResponse {
        $this->slotService->create(
            $curriculum,
            $term,
            $request->validated(),
            $request->user()->id
        );

        return back()->with('success', 'Curriculum Slot added successfully.');
    }

    public function updateSlot(
        UpdateCurriculumSlotRequest $request,
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot
    ): RedirectResponse {
        $this->slotService->update(
            $curriculum,
            $term,
            $slot,
            $request->validated(),
            $request->user()->id
        );

        return back()->with('success', 'Curriculum Slot updated successfully.');
    }

    public function statusSlot(
        Request $request,
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot
    ): RedirectResponse {
        abort_unless(
            $request->user()->hasPermission('curriculum.update'),
            403
        );

        $validated = $request->validate([
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
        ]);

        $this->slotService->setStatus(
            $curriculum,
            $term,
            $slot,
            $validated['status'],
            $request->user()->id
        );

        return back()->with('success', 'Curriculum Slot status updated successfully.');
    }


    public function courseMappings(
        Request $request,
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot
    ): Response {
        abort_unless($request->user()->hasPermission('curriculum.view'), 403);

        abort_unless(
            (int) $term->curriculum_id === (int) $curriculum->id &&
            (int) $slot->curriculum_term_id === (int) $term->id,
            404
        );

        $curriculum->load([
            'programTemplate:id,name,code',
            'academicSession:id,name,code',
        ]);

        $category = DB::table('course_categories')
            ->where('id', $slot->course_category_id)
            ->value('name');

        $courseType = DB::table('course_types')
            ->where('id', $slot->course_type_id)
            ->value('name');

        $mappings = DB::table('curriculum_course_mappings as mapping')
            ->join('courses', 'courses.id', '=', 'mapping.course_id')
            ->leftJoin(
                'academic_disciplines as discipline',
                'discipline.id',
                '=',
                'mapping.discipline_id'
            )
            ->leftJoin(
                'academic_disciplines as specialization',
                'specialization.id',
                '=',
                'mapping.specialization_id'
            )
            ->where('mapping.curriculum_slot_id', $slot->id)
            ->orderByRaw('COALESCE(mapping.display_order, 65535)')
            ->orderBy('mapping.id')
            ->get([
                'mapping.id',
                'mapping.course_id',
                'mapping.discipline_id',
                'mapping.specialization_id',
                'mapping.display_order',
                'mapping.status',
                'courses.code as course_code',
                'courses.name as course_name',
                'discipline.name as discipline_name',
                'specialization.name as specialization_name',
            ]);

        $mappedCourseIds = $mappings
            ->pluck('course_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $availableCourses = DB::table('courses')
            ->where('university_id', $curriculum->university_id)
            ->where('course_category_id', $slot->course_category_id)
            ->where('course_type_id', $slot->course_type_id)
            ->where('status', 'ACTIVE')
            ->when(
                count($mappedCourseIds) > 0,
                fn ($query) => $query->whereNotIn('id', $mappedCourseIds)
            )
            ->orderBy('display_order')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'code',
            ]);

        $programDisciplines = DB::table('program_template_disciplines as ptd')
            ->join(
                'academic_disciplines as discipline',
                'discipline.id',
                '=',
                'ptd.discipline_id'
            )
            ->where('ptd.program_template_id', $curriculum->program_template_id)
            ->where('discipline.university_id', $curriculum->university_id)
            ->where('discipline.kind', 'DISCIPLINE')
            ->where('discipline.status', 'ACTIVE')
            ->orderBy('discipline.display_order')
            ->orderBy('discipline.name')
            ->get([
                'ptd.id as program_template_discipline_id',
                'discipline.id',
                'discipline.name',
                'discipline.code',
            ]);

        $specializations = DB::table(
            'program_template_discipline_specializations as ptds'
        )
            ->join(
                'academic_disciplines as specialization',
                'specialization.id',
                '=',
                'ptds.specialization_id'
            )
            ->whereIn(
                'ptds.program_template_discipline_id',
                $programDisciplines->pluck('program_template_discipline_id')
            )
            ->where('specialization.university_id', $curriculum->university_id)
            ->where('specialization.kind', 'SPECIALIZATION')
            ->where('specialization.status', 'ACTIVE')
            ->orderBy('specialization.display_order')
            ->orderBy('specialization.name')
            ->get([
                'ptds.program_template_discipline_id',
                'specialization.id',
                'specialization.name',
                'specialization.code',
            ]);

        $disciplineOptions = $programDisciplines->map(
            fn ($discipline) => [
                'id' => $discipline->id,
                'name' => $discipline->name,
                'code' => $discipline->code,
                'specializations' => $specializations
                    ->where(
                        'program_template_discipline_id',
                        $discipline->program_template_discipline_id
                    )
                    ->map(fn ($specialization) => [
                        'id' => $specialization->id,
                        'name' => $specialization->name,
                        'code' => $specialization->code,
                    ])
                    ->values(),
            ]
        )->values();

        return Inertia::render(
            'admin/curricula/structure/course-mappings',
            [
                'curriculum' => [
                    'id' => $curriculum->id,
                    'code' => $curriculum->code,
                    'name' => $curriculum->name,
                    'version' => $curriculum->version,
                    'lifecycle_status' => $curriculum->lifecycle_status,
                    'program_template' => $curriculum->programTemplate,
                    'academic_session' => $curriculum->academicSession,
                ],
                'term' => [
                    'id' => $term->id,
                    'sequence_no' => $term->sequence_no,
                    'name' => $term->name,
                ],
                'slot' => [
                    'id' => $slot->id,
                    'name' => $slot->name,
                    'course_category_id' => $slot->course_category_id,
                    'course_category_name' => $category ?? 'Unknown',
                    'course_type_id' => $slot->course_type_id,
                    'course_type_name' => $courseType ?? 'Unknown',
                    'credits' => $slot->credits,
                    'selection_mode' => $slot->selection_mode,
                    'min_selection' => $slot->min_selection,
                    'max_selection' => $slot->max_selection,
                    'status' => $slot->status,
                ],
                'mappings' => $mappings,
                'availableCourses' => $availableCourses,
                'disciplineOptions' => $disciplineOptions,
                'permissions' => [
                    'update' => $request
                        ->user()
                        ->hasPermission('curriculum.update'),
                ],
                'structureEditable' =>
                    $curriculum->lifecycle_status === 'DRAFT',
            ]
        );
    }

    public function storeCourseMapping(
        StoreCurriculumCourseMappingRequest $request,
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot
    ): RedirectResponse {
        $validated = $request->validated();

        $this->courseMappingService->create(
            $curriculum,
            $term,
            $slot,
            (int) $validated['discipline_id'],
            isset($validated['specialization_id'])
                ? (int) $validated['specialization_id']
                : null,
            (int) $validated['course_id'],
            $request->user()->id
        );

        return back()->with('success', 'Course / Paper mapped successfully.');
    }

    public function statusCourseMapping(
        Request $request,
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot,
        CurriculumCourseMapping $mapping
    ): RedirectResponse {
        abort_unless(
            $request->user()->hasPermission('curriculum.update'),
            403
        );

        $validated = $request->validate([
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
        ]);

        $this->courseMappingService->setStatus(
            $curriculum,
            $term,
            $slot,
            $mapping,
            $validated['status'],
            $request->user()->id
        );

        return back()->with(
            'success',
            'Course / Paper Mapping status updated successfully.'
        );
    }


    public function updateCourseMappingOrder(
        UpdateCurriculumCourseMappingOrderRequest $request,
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot,
        CurriculumCourseMapping $mapping
    ): RedirectResponse {
        $this->courseMappingService->updateDisplayOrder(
            $curriculum,
            $term,
            $slot,
            $mapping,
            (int) $request->validated('display_order'),
            $request->user()->id
        );

        return back()->with(
            'success',
            'Course / Paper Mapping order updated successfully.'
        );
    }


    public function updateCourseMapping(
        UpdateCurriculumCourseMappingRequest $request,
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot,
        CurriculumCourseMapping $mapping
    ): RedirectResponse {
        $validated = $request->validated();

        $this->courseMappingService->update(
            $curriculum,
            $term,
            $slot,
            $mapping,
            (int) $validated['discipline_id'],
            isset($validated['specialization_id']) ? (int) $validated['specialization_id'] : null,
            (int) $validated['course_id'],
            $request->user()->id
        );

        return back()->with('success', 'Course / Paper Mapping updated successfully.');
    }


    public function validateStructure(
        Request $request,
        Curriculum $curriculum
    ): \Illuminate\Http\JsonResponse {
        abort_unless(
            $request->user()->hasPermission('curriculum.view'),
            403
        );

        return response()->json(
            $this->structureValidationService->validate($curriculum)
        );
    }


    public function cloneTerm(
        CloneCurriculumTermRequest $request,
        Curriculum $curriculum,
        CurriculumTerm $term
    ): RedirectResponse {
        $this->cloneService->cloneTerm(
            $curriculum,
            $term,
            $request->validated(),
            $request->user()->id
        );

        return back()->with(
            'success',
            'Term / Semester and its complete structure cloned successfully.'
        );
    }


    public function cloneSlot(
        CloneCurriculumSlotRequest $request,
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot
    ): RedirectResponse {
        $this->cloneService->cloneSlot(
            $curriculum,
            $term,
            $slot,
            $request->validated(),
            $request->user()->id
        );

        return back()->with(
            'success',
            'Curriculum Slot and its Course / Paper Mappings cloned successfully.'
        );
    }


    public function deleteTerm(
        Request $request,
        Curriculum $curriculum,
        CurriculumTerm $term
    ): RedirectResponse {
        abort_unless($request->user()->hasPermission('curriculum.update'), 403);

        $this->deleteService->deleteTerm(
            $curriculum,
            $term,
            $request->user()->id
        );

        return back()->with('success', 'Term / Semester deleted successfully.');
    }

    public function deleteSlot(
        Request $request,
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot
    ): RedirectResponse {
        abort_unless($request->user()->hasPermission('curriculum.update'), 403);

        $this->deleteService->deleteSlot(
            $curriculum,
            $term,
            $slot,
            $request->user()->id
        );

        return back()->with('success', 'Curriculum Slot deleted successfully.');
    }

    public function deleteCourseMapping(
        Request $request,
        Curriculum $curriculum,
        CurriculumTerm $term,
        CurriculumSlot $slot,
        CurriculumCourseMapping $mapping
    ): RedirectResponse {
        abort_unless($request->user()->hasPermission('curriculum.update'), 403);

        $this->deleteService->deleteMapping(
            $curriculum,
            $term,
            $slot,
            $mapping,
            $request->user()->id
        );

        return back()->with('success', 'Course / Paper Mapping deleted successfully.');
    }

    public function cloneTargetTerms(Request $request, Curriculum $curriculum, Curriculum $targetCurriculum)
    {
        abort_unless($request->user()->hasPermission('curriculum.update'), 403);
        abort_unless(
            (int) $targetCurriculum->university_id === (int) $curriculum->university_id &&
            (int) $targetCurriculum->program_template_id === (int) $curriculum->program_template_id &&
            $targetCurriculum->lifecycle_status === 'DRAFT',
            422
        );

        return response()->json([
            'terms' => CurriculumTerm::query()
                ->where('curriculum_id', $targetCurriculum->id)
                ->orderBy('sequence_no')
                ->get(['id', 'sequence_no', 'name'])
                ->map(function (CurriculumTerm $targetTerm) {
                    $nextOrder = (int) CurriculumSlot::query()
                        ->where('curriculum_term_id', $targetTerm->id)
                        ->max('display_order') + 1;
                    return [
                        'id' => $targetTerm->id,
                        'sequence_no' => $targetTerm->sequence_no,
                        'name' => $targetTerm->name,
                        'next_slot_order' => max($nextOrder, 1),
                    ];
                })->values(),
        ]);
    }

}
