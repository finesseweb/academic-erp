<?php

namespace App\Http\Controllers;

use App\Models\CourseType;
use App\Models\University;
use App\Services\AcademicMasterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CourseTypeController extends Controller
{
    public function __construct(private AcademicMasterService $service) {}

    public function index(Request $request): Response
    {
        $this->authorize($request, 'view');
        $university = University::firstOrFail();

        return Inertia::render('academic-masters/course-types', [
            'courseTypes' => CourseType::query()
                ->where('university_id', $university->id)
                ->orderBy('display_order')
                ->orderBy('name')
                ->get(),
            'can' => $this->can($request),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize($request, 'create');
        $university = University::firstOrFail();

        $this->service->create(
            CourseType::class,
            $this->validated($request, $university->id),
            $university->id,
            $request->user()->id,
            $request->ip(),
            'CourseType',
            'COURSE_TYPE',
        );

        return back()->with('success', 'Course type created.');
    }

    public function update(Request $request, CourseType $courseType): RedirectResponse
    {
        $this->authorize($request, 'update');
        $this->owned($courseType);

        $this->service->update(
            $courseType,
            $this->validated($request, $courseType->university_id, $courseType),
            $request->user()->id,
            $request->ip(),
            'CourseType',
            'COURSE_TYPE',
        );

        return back()->with('success', 'Course type updated.');
    }

    public function status(Request $request, CourseType $courseType): RedirectResponse
    {
        $this->authorize($request, 'disable');
        $this->owned($courseType);

        $status = $request->validate([
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ])['status'];

        $this->service->status(
            $courseType,
            $status,
            $request->user()->id,
            $request->ip(),
            'CourseType',
            'COURSE_TYPE',
        );

        return back()->with('success', 'Course type status updated.');
    }

    private function validated(Request $request, int $universityId, ?CourseType $record = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => [
                'required',
                'string',
                'max:40',
                Rule::unique('course_types')
                    ->where('university_id', $universityId)
                    ->ignore($record),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'display_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ]);
    }

    private function authorize(Request $request, string $action): void
    {
        abort_unless($request->user()->hasPermission("course_type.$action"), 403);
    }

    private function owned(CourseType $courseType): void
    {
        abort_unless($courseType->university_id === University::firstOrFail()->id, 404);
    }

    private function can(Request $request): array
    {
        return [
            'create' => $request->user()->hasPermission('course_type.create'),
            'update' => $request->user()->hasPermission('course_type.update'),
            'disable' => $request->user()->hasPermission('course_type.disable'),
        ];
    }
}
